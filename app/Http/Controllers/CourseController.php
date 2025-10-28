<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCourseRequest;
use App\Http\Requests\UpdateCourseRequest;
use App\Models\Content;
use App\Models\Course;
use App\Models\Module;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CourseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $courses = Course::with(['modules.allContents'])->get();

        return response()->json($courses);
    }

    /**
     * Show the courses index view
     */
    public function indexView()
    {
        return view('courses.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('courses.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCourseRequest $request)
    {
        // Check authorization
        $this->authorize('create', Course::class);

        try {
            DB::beginTransaction();

            // Handle file uploads for course
            $thumbnailPath = null;
            $featureVideoPath = null;

            if ($request->hasFile('thumbnail')) {
                $thumbnailPath = $request->file('thumbnail')->store('courses/thumbnails', 'public');
            }

            if ($request->hasFile('feature_video')) {
                $featureVideoPath = $request->file('feature_video')->store('courses/videos', 'public');
            }

            $course = Course::create([
                'user_id' => auth()->id(),
                'title' => $request->title,
                'description' => $request->description,
                'category' => $request->category,
                'thumbnail' => $thumbnailPath,
                'feature_video' => $featureVideoPath,
            ]);

            foreach ($request->modules as $moduleIndex => $moduleData) {
                $module = Module::create([
                    'course_id' => $course->id,
                    'title' => $moduleData['title'],
                    'description' => $moduleData['description'] ?? null,
                    'order' => $moduleIndex,
                ]);

                foreach ($moduleData['contents'] as $contentIndex => $contentData) {
                    $this->createContentRecursive($module->id, $contentData, $contentIndex, null, $request);
                }
            }

            DB::commit();

            Log::info('Course created successfully', ['course_id' => $course->id, 'user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Course created successfully',
                'course' => $course->load(['modules.allContents']),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to create course', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create course. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while creating the course.',
            ], 500);
        }
    }

    /**
     * Helper function to create content recursively
     */
    private function createContentRecursive($moduleId, $contentData, $order, $parentId = null, $request = null)
    {
        // Handle file upload for content if present and request is provided
        $filePath = null;
        if ($request && isset($contentData['hasFile']) && $contentData['hasFile'] && isset($contentData['fileKey'])) {
            $fileKey = $contentData['fileKey'];
            if ($request->hasFile($fileKey)) {
                $type = $contentData['type'] ?? 'text';
                $folder = match ($type) {
                    'video' => 'contents/videos',
                    'document' => 'contents/documents',
                    default => 'contents/files',
                };
                $filePath = $request->file($fileKey)->store($folder, 'public');
            }
        }

        $content = Content::create([
            'module_id' => $moduleId,
            'parent_id' => $parentId,
            'title' => $contentData['title'],
            'body' => $contentData['body'] ?? null,
            'type' => $contentData['type'] ?? 'text',
            'order' => $order,
            'file_path' => $filePath,
        ]);

        if (isset($contentData['children']) && is_array($contentData['children'])) {
            foreach ($contentData['children'] as $childIndex => $childData) {
                $this->createContentRecursive($moduleId, $childData, $childIndex, $content->id, $request);
            }
        }

        return $content;
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $course = Course::with(['modules.allContents.children'])->findOrFail($id);

        return response()->json($course);
    }

    /**
     * Show the view for displaying a course
     */
    public function showView(string $id)
    {
        $course = Course::with(['modules.contents.children'])->findOrFail($id);

        return view('courses.show', compact('course'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $course = Course::with(['modules.allContents'])->findOrFail($id);
        
        $this->authorize('update', $course);

        return view('courses.edit', compact('course'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCourseRequest $request, string $id)
    {
        $course = Course::findOrFail($id);
        
        $this->authorize('update', $course);

        try {
            DB::beginTransaction();
            
            // Handle file uploads
            $updateData = [
                'title' => $request->title,
                'description' => $request->description,
                'category' => $request->category,
            ];

            if ($request->hasFile('thumbnail')) {
                // Delete old thumbnail if exists
                if ($course->thumbnail) {
                    Storage::disk('public')->delete($course->thumbnail);
                }
                $updateData['thumbnail'] = $request->file('thumbnail')->store('courses/thumbnails', 'public');
            }

            if ($request->hasFile('feature_video')) {
                // Delete old video if exists
                if ($course->feature_video) {
                    Storage::disk('public')->delete($course->feature_video);
                }
                $updateData['feature_video'] = $request->file('feature_video')->store('courses/videos', 'public');
            }

            $course->update($updateData);

            // Delete existing modules and contents (cascade will handle contents)
            $course->modules()->delete();

            foreach ($request->modules as $moduleIndex => $moduleData) {
                $module = Module::create([
                    'course_id' => $course->id,
                    'title' => $moduleData['title'],
                    'description' => $moduleData['description'] ?? null,
                    'order' => $moduleIndex,
                ]);

                foreach ($moduleData['contents'] as $contentIndex => $contentData) {
                    $this->createContentRecursive($module->id, $contentData, $contentIndex, null, $request);
                }
            }

            DB::commit();

            Log::info('Course updated successfully', ['course_id' => $course->id, 'user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Course updated successfully',
                'course' => $course->load(['modules.allContents']),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Failed to update course', [
                'course_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to update course. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while updating the course.',
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            $course = Course::with('modules.allContents')->findOrFail($id);
            
            $this->authorize('delete', $course);
            
            // Delete course files
            if ($course->thumbnail) {
                Storage::disk('public')->delete($course->thumbnail);
            }
            if ($course->feature_video) {
                Storage::disk('public')->delete($course->feature_video);
            }

            // Delete content files
            foreach ($course->modules as $module) {
                foreach ($module->allContents as $content) {
                    if ($content->file_path) {
                        Storage::disk('public')->delete($content->file_path);
                    }
                }
            }

            $course->delete();

            Log::info('Course deleted successfully', ['course_id' => $id, 'user_id' => auth()->id()]);

            return response()->json([
                'success' => true,
                'message' => 'Course deleted successfully',
            ]);
        } catch (\Exception $e) {
            Log::error('Failed to delete course', [
                'course_id' => $id,
                'error' => $e->getMessage(),
                'user_id' => auth()->id() ?? 'guest',
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to delete course. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'An error occurred while deleting the course.',
            ], 500);
        }
    }
}
