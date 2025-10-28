<x-app-layout>
    <x-slot name="header">
        <div class="flex justify-between items-center">
            <h2 class="font-semibold text-xl text-gray-800 leading-tight">
                {{ __('All Courses') }}
            </h2>
            @auth
                @can('create', App\Models\Course::class)
                    <a href="{{ route('courses.create') }}" class="inline-flex items-center px-4 py-2 bg-gray-800 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-gray-700 focus:bg-gray-700 active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150">
                        + Create New Course
                    </a>
                @endcan
            @endauth
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div id="message-container"></div>

            <div id="coursesContainer" class="courses-grid">
                <div class="loading-spinner">
                    <div class="loading" style="width: 2rem; height: 2rem; border-width: 3px; margin: 0 auto 1rem;"></div>
                    <p>Loading courses...</p>
                </div>
            </div>
        </div>
    </div>

<x-slot name="scripts">
<script>
$(document).ready(function() {
    loadCourses();
    function loadCourses() {
        $.ajax({
            url: '/api/courses',
            method: 'GET',
            success: function(response) {
                displayCourses(response);
            },
            error: function(xhr) {
                showMessage('Failed to load courses.', 'error');
                $('#coursesContainer').html('<p class="error-message">Failed to load courses. Please try again.</p>');
            }
        });
    }

    function displayCourses(courses) {
        const container = $('#coursesContainer');

        if (courses.length === 0) {
            container.html(`
                <div class="no-courses" style="padding: 4rem 2rem; text-align: center;">
                    <div style="font-size: 4rem; margin-bottom: 1rem;">📚</div>
                    <h3 style="font-size: 1.25rem; font-weight: 600; color: #374151; margin-bottom: 0.5rem;">No courses yet</h3>
                    <p style="color: #6b7280; margin-bottom: 1.5rem;">Get started by creating your first course!</p>
                    <a href="{{ route('courses.create') }}" class="btn btn-primary">+ Create Your First Course</a>
                </div>
            `);
            return;
        }

        let html = '';
        courses.forEach(function(course) {
            const thumbnailUrl = course.thumbnail
                ? '/storage/' + course.thumbnail
                : '/images/default-course-thumbnail.png';

            const moduleCount = course.modules ? course.modules.length : 0;
            const contentCount = course.modules
                ? course.modules.reduce((sum, module) => sum + (module.all_contents ? module.all_contents.length : 0), 0)
                : 0;

            html += `
                <div class="course-card">
                    ${course.thumbnail ? `
                    <div class="course-card-thumbnail">
                        <img src="${thumbnailUrl}" alt="${course.title}" onerror="this.src='/images/default-course-thumbnail.png'">
                    </div>
                    ` : ''}
                    <div class="course-card-content">
                        <h3>${course.title}</h3>
                        ${course.category ? `<div class="category-badge" style="margin-bottom: 0.75rem;"><span style="display: inline-block; padding: 0.25rem 0.75rem; background: #e0e7ff; color: #4338ca; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">🏷️ ${course.category}</span></div>` : ''}
                        ${course.description ? `<p class="course-description">${truncate(course.description, 150)}</p>` : ''}
                        <div class="course-meta">
                            <span class="meta-item">📚 ${moduleCount} module${moduleCount !== 1 ? 's' : ''}</span>
                            <span class="meta-item">📝 ${contentCount} content item${contentCount !== 1 ? 's' : ''}</span>
                        </div>
                        <div class="course-actions">
                            <a href="/courses/${course.id}" class="btn btn-sm btn-primary">View Course</a>
                            ${course.can_edit ? `<a href="/courses/${course.id}/edit" class="btn btn-sm btn-secondary">Edit</a>` : ''}
                            ${course.can_delete ? `<button class="btn btn-sm btn-danger delete-course" data-id="${course.id}">Delete</button>` : ''}
                        </div>
                    </div>
                </div>
            `;
        });

        container.html(html);
    }

    function truncate(str, length) {
        if (str.length <= length) return str;
        return str.substring(0, length) + '...';
    }

    // Delete course
    $(document).on('click', '.delete-course', function() {
        const courseId = $(this).data('id');

        if (!confirm('Are you sure you want to delete this course? This action cannot be undone.')) {
            return;
        }

        $.ajax({
            url: `/api/courses/${courseId}`,
            method: 'DELETE',
            success: function(response) {
                showMessage('Course deleted successfully!', 'success');
                loadCourses();
            },
            error: function(xhr) {
                showMessage('Failed to delete course.', 'error');
            }
        });
    });

    function showMessage(message, type) {
        const messageClass = type === 'success' ? 'success-message' : 'error-message';
        const messageHtml = `<div class="${messageClass}">${message}</div>`;
        $('#message-container').html(messageHtml);
        $('html, body').animate({ scrollTop: 0 }, 'fast');

        if (type === 'success') {
            setTimeout(() => {
                $('#message-container').html('');
            }, 3000);
        }
    }
});
</script>
</x-slot>
</x-app-layout>
