<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ $course->title }} by {{ $course->user->name }}
            </h2>
            <div class="flex gap-3">
                <a href="{{ route('courses.index') }}" class="inline-flex items-center px-4 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                    ← All Courses
                </a>
                @can('update', $course)
                    <a href="{{ route('courses.edit', $course->id) }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        Edit Course
                    </a>
                @endcan
            </div>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div class="course-detail">
                        <div class="course-header-content">

                        @if($course->category)
                        <div class="course-category mb-4">
                            <span class="category-badge">{{ $course->category }}</span>
                        </div>
                        @endif

                        @if($course->thumbnail)
                        <div class="course-thumbnail mb-6">
                            <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="{{ $course->title }}" class="rounded-lg shadow-md max-h-96 w-full object-cover" />
                        </div>
                        @endif

                        @if($course->description)
                        <div class="course-description mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">About This Course</h3>
                            <p class="text-gray-600">{{ $course->description }}</p>
                        </div>
                        @endif

                        @if($course->feature_video)
                        <div class="feature-video mb-6">
                            <h3 class="text-lg font-medium text-gray-900 mb-2">Feature Video</h3>
                            <x-video-player
                                :src="$course->feature_video"
                                :poster="$course->thumbnail"
                                class="max-w-4xl"
                            />
                        </div>
                        @endif
                    </div>

                    <div class="course-content mt-8">
                        <h3 class="text-lg font-medium text-gray-900 mb-4">📚 Course Modules</h3>

                        @if($course->modules->count() > 0)
                        <div class="modules-list space-y-4">
                            @foreach($course->modules as $moduleIndex => $module)
                            <div class="module-card bg-gray-50 p-6 rounded-lg" id="module-{{ $module->id }}">
                                <div class="flex justify-between items-center mb-4">
                                    <h4 class="text-base font-semibold text-gray-900">
                                        <span class="text-indigo-600">📖 Module {{ $moduleIndex + 1 }}:</span>
                                        {{ $module->title }}
                                    </h4>
                                    <button type="button" class="btn-toggle text-gray-600 hover:text-gray-900 transition" data-target="module-content-{{ $module->id }}" aria-expanded="true">
                                        <span class="toggle-icon text-lg">▼</span>
                                    </button>
                                </div>

                                @if($module->description)
                                <div class="module-description mb-3">
                                    <p class="text-sm text-gray-600">{{ $module->description }}</p>
                                </div>
                                @endif

                                <div class="module-content" id="module-content-{{ $module->id }}">
                                    @if($module->contents->count() > 0)
                                    <div class="contents-list space-y-3">
                                        @foreach($module->contents as $content)
                                            @include('courses.partials.content-item', ['content' => $content, 'level' => 0])
                                        @endforeach
                                    </div>
                                    @else
                                    <p class="no-content text-sm text-gray-500 italic">No content available in this module.</p>
                                    @endif
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="no-modules text-gray-500 text-center py-8">No modules available in this course.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

<x-slot name="scripts">
<script>
    $(document).ready(function() {
        // Toggle module content
    $('.btn-toggle').click(function() {
        const targetId = $(this).data('target');
        const target = $('#' + targetId);
        const icon = $(this).find('.toggle-icon');

        target.slideToggle(300);

        if ($(this).attr('aria-expanded') === 'true') {
            $(this).attr('aria-expanded', 'false');
            icon.text('▶');
        } else {
            $(this).attr('aria-expanded', 'true');
            icon.text('▼');
        }
    });

    // Toggle nested content
    $('.content-toggle').click(function() {
        const targetId = $(this).data('target');
        const target = $('#' + targetId);
        const icon = $(this).find('.toggle-icon');

        target.slideToggle(300);

        if ($(this).attr('aria-expanded') === 'true') {
            $(this).attr('aria-expanded', 'false');
            icon.text('▶');
        } else {
            $(this).attr('aria-expanded', 'true');
            icon.text('▼');
        }
    });
});
</script>
</x-slot>
</x-app-layout>
