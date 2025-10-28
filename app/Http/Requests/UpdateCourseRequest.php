<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCourseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via policy
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation()
    {
        // Parse modules JSON if it's a string (from FormData)
        if ($this->has('modules') && is_string($this->modules)) {
            $this->merge([
                'modules' => json_decode($this->modules, true)
            ]);
        }
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Course basic fields
            'title' => 'required|string|min:3|max:255',
            'description' => 'nullable|string|max:5000',
            'category' => 'nullable|string|max:255',
            
            // File uploads (optional on update)
            'thumbnail' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'feature_video' => 'nullable|file|mimes:mp4,mov,avi,wmv|max:51200',
            
            // Modules
            'modules' => 'required|array|min:1',
            'modules.*.title' => 'required|string|min:3|max:255',
            'modules.*.description' => 'nullable|string|max:2000',
            
            // Contents
            'modules.*.contents' => 'required|array|min:1',
            'modules.*.contents.*.title' => 'required|string|min:3|max:255',
            'modules.*.contents.*.body' => 'nullable|string|max:10000',
            'modules.*.contents.*.type' => 'required|string|in:text,video,document,quiz',
            
            // Nested contents (recursive)
            'modules.*.contents.*.children' => 'nullable|array',
            'modules.*.contents.*.children.*.title' => 'required|string|min:3|max:255',
            'modules.*.contents.*.children.*.body' => 'nullable|string|max:10000',
            'modules.*.contents.*.children.*.type' => 'required|string|in:text,video,document,quiz',
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'title.required' => 'The course title is required.',
            'title.min' => 'The course title must be at least 3 characters.',
            'title.max' => 'The course title must not exceed 255 characters.',
            
            'description.max' => 'The course description is too long (maximum 5000 characters).',
            
            'thumbnail.image' => 'The thumbnail must be an image file.',
            'thumbnail.mimes' => 'The thumbnail must be a file of type: jpeg, png, jpg, gif.',
            'thumbnail.max' => 'The thumbnail size must not exceed 2MB.',
            
            'feature_video.mimes' => 'The feature video must be a file of type: mp4, mov, avi, wmv.',
            'feature_video.max' => 'The feature video size must not exceed 50MB.',
            
            'modules.required' => 'At least one module is required.',
            'modules.min' => 'At least one module is required.',
            'modules.*.title.required' => 'All module titles are required.',
            'modules.*.title.min' => 'Module titles must be at least 3 characters.',
            'modules.*.title.max' => 'Module titles must not exceed 255 characters.',
            
            'modules.*.contents.required' => 'Each module must have at least one content item.',
            'modules.*.contents.min' => 'Each module must have at least one content item.',
            'modules.*.contents.*.title.required' => 'All content titles are required.',
            'modules.*.contents.*.title.min' => 'Content titles must be at least 3 characters.',
            'modules.*.contents.*.type.required' => 'Content type is required.',
            'modules.*.contents.*.type.in' => 'Invalid content type. Must be: text, video, document, or quiz.',
        ];
    }

    /**
     * Get custom attributes for validator errors.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return [
            'modules.*.title' => 'module title',
            'modules.*.description' => 'module description',
            'modules.*.contents' => 'module contents',
            'modules.*.contents.*.title' => 'content title',
            'modules.*.contents.*.body' => 'content body',
            'modules.*.contents.*.type' => 'content type',
        ];
    }
}
