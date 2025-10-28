<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            {{ __('Edit Course') }}: {{ $course->title }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <div id="message-container"></div>

                    <form id="courseForm" enctype="multipart/form-data">
    <div class="form-group">
        <label for="courseTitle">Course Title *</label>
        <input type="text" id="courseTitle" name="title" value="{{ $course->title }}" required>
    </div>

    <div class="form-group">
        <label for="courseDescription">Course Description</label>
        <textarea id="courseDescription" name="description" rows="3">{{ $course->description }}</textarea>
    </div>

    <div class="form-group">
        <label for="courseCategory">Category</label>
        <input type="text" id="courseCategory" name="category" value="{{ $course->category }}" placeholder="e.g., Tax, Finance, Technology">
        <small class="form-text">Enter the course category (e.g., Tax, Finance, Technology)</small>
    </div>

    <div class="form-group">
        <label for="courseThumbnail">Course Thumbnail</label>
        @if($course->thumbnail)
        <div class="current-file mb-2">
            <img src="{{ asset('storage/' . $course->thumbnail) }}" alt="Current thumbnail" style="max-width: 200px; max-height: 200px; border-radius: 0.375rem; border: 2px solid #e5e7eb;">
            <p class="text-sm text-gray-600 mt-1">Current thumbnail (upload new to replace)</p>
        </div>
        @endif
        <input type="file" id="courseThumbnail" name="thumbnail" accept="image/jpeg,image/png,image/jpg,image/gif">
        <small class="form-text">Max size: 2MB. Accepted formats: JPEG, PNG, JPG, GIF</small>
        <div class="file-preview" id="thumbnailPreview" style="display: none; margin-top: 0.5rem;">
            <img src="" alt="Thumbnail preview" style="max-width: 200px; max-height: 200px; border-radius: 0.375rem; border: 2px solid #e5e7eb;">
        </div>
        <div class="validation-error" id="thumbnailError" style="display: none;"></div>
    </div>

    <div class="form-group">
        <label for="courseFeatureVideo">Feature Video</label>
        @if($course->feature_video)
        <div class="current-file mb-2">
            <p class="text-sm text-gray-600">Current video: {{ basename($course->feature_video) }}</p>
            <p class="text-sm text-gray-500">(upload new to replace)</p>
        </div>
        @endif
        <input type="file" id="courseFeatureVideo" name="feature_video" accept="video/mp4,video/x-msvideo,video/quicktime,video/x-ms-wmv">
        <small class="form-text">Max size: 50MB. Accepted formats: MP4, MOV, AVI, WMV</small>
        <div class="validation-error" id="videoError" style="display: none;"></div>
    </div>

    <div class="section-header">
        <h2 class="section-title">📚 Course Modules</h2>
        <p class="section-subtitle">Add modules to organize your course content. Each module can contain multiple content items.</p>
    </div>

    <div id="modulesContainer"></div>

    <div class="add-btn-container">
        <button type="button" class="btn btn-primary" id="addModuleBtn">+ Add Module</button>
    </div>

    <div class="form-actions">
        <a href="{{ route('courses.show', $course->id) }}" class="btn btn-secondary">Cancel</a>
        <button type="submit" class="btn btn-success" id="submitBtn">
            <span class="btn-text">Update Course</span>
        </button>
    </div>
</form>
                </div>
            </div>
        </div>
    </div>

    <x-slot name="scripts">
        <script>
        $(document).ready(function() {
            let moduleCounter = 0;
            let contentCounters = {};
            const courseId = {{ $course->id }};
            const courseData = @json($course);

    // Setup CSRF token for AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    // File validation
    $('#courseThumbnail').on('change', function() {
        const file = this.files[0];
        const errorDiv = $('#thumbnailError');
        const preview = $('#thumbnailPreview');
        errorDiv.hide().text('');
        
        if (file) {
            // Validate file size (2MB)
            if (file.size > 2 * 1024 * 1024) {
                errorDiv.text('Thumbnail must be less than 2MB').show();
                this.value = '';
                preview.hide();
                return;
            }
            
            // Validate file type
            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                errorDiv.text('Please upload a valid image file (JPEG, PNG, GIF)').show();
                this.value = '';
                preview.hide();
                return;
            }
            
            // Show preview
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.find('img').attr('src', e.target.result);
                preview.show();
            };
            reader.readAsDataURL(file);
        } else {
            preview.hide();
        }
    });

    $('#courseFeatureVideo').on('change', function() {
        const file = this.files[0];
        const errorDiv = $('#videoError');
        errorDiv.hide().text('');
        
        if (file) {
            // Validate file size (50MB)
            if (file.size > 50 * 1024 * 1024) {
                errorDiv.text('Video must be less than 50MB').show();
                this.value = '';
                return;
            }
            
            // Validate file type
            const validTypes = ['video/mp4', 'video/quicktime', 'video/x-msvideo', 'video/x-ms-wmv'];
            if (!validTypes.includes(file.type)) {
                errorDiv.text('Please upload a valid video file (MP4, MOV, AVI, WMV)').show();
                this.value = '';
                return;
            }
        }
    });

    // Real-time validation for required fields
    $('#courseTitle').on('blur', function() {
        const value = $(this).val().trim();
        if (!value) {
            showFieldError(this, 'Course title is required');
        } else if (value.length < 3) {
            showFieldError(this, 'Course title must be at least 3 characters');
        } else if (value.length > 255) {
            showFieldError(this, 'Course title must not exceed 255 characters');
        } else {
            clearFieldError(this);
        }
    });

    function showFieldError(field, message) {
        $(field).addClass('error-field');
        let errorDiv = $(field).next('.field-error');
        if (errorDiv.length === 0) {
            errorDiv = $('<div class="field-error"></div>');
            $(field).after(errorDiv);
        }
        errorDiv.text(message).show();
    }

    function clearFieldError(field) {
        $(field).removeClass('error-field');
        $(field).next('.field-error').remove();
    }

    // Add module
    $('#addModuleBtn').click(function() {
        addModule();
    });

    function addModule(moduleData = null) {
        moduleCounter++;
        contentCounters[moduleCounter] = 0;

        const moduleHtml = `
            <div class="module" data-module-id="${moduleCounter}">
                <div class="module-header">
                    <div class="module-header-left">
                        <span class="module-number">#${moduleCounter}</span>
                        <span class="module-title">Module ${moduleCounter}</span>
                        <button type="button" class="btn-toggle-module" data-module-id="${moduleCounter}" aria-expanded="true">
                            <span class="toggle-icon">▼</span>
                        </button>
                    </div>
                    <button type="button" class="btn btn-danger btn-sm remove-module">🗑️ Remove</button>
                </div>

                <div class="module-body" id="module-body-${moduleCounter}">
                    <div class="form-group">
                        <label>Module Title *</label>
                        <input type="text" name="modules[${moduleCounter}][title]" value="${moduleData ? moduleData.title : ''}" required placeholder="Enter module title">
                    </div>

                    <div class="form-group">
                        <label>Module Description</label>
                        <textarea name="modules[${moduleCounter}][description]" rows="2" placeholder="Brief description of this module (optional)">${moduleData ? moduleData.description || '' : ''}</textarea>
                    </div>

                    <div class="content-section-header">
                        <h3 class="content-section-title">📝 Module Contents</h3>
                        <p class="content-section-subtitle">Add content items to this module. You can nest content for better organization.</p>
                    </div>
                    <div class="contents-container" data-module-id="${moduleCounter}"></div>

                    <div class="add-btn-container">
                        <button type="button" class="btn btn-secondary btn-sm add-content-btn" data-module-id="${moduleCounter}">+ Add Content</button>
                    </div>
                </div>
            </div>
        `;

        $('#modulesContainer').append(moduleHtml);
        
        // Add existing contents or a default one
        if (moduleData && moduleData.all_contents && moduleData.all_contents.length > 0) {
            moduleData.all_contents.forEach(content => {
                if (!content.parent_id) {
                    addContent(moduleCounter, null, content);
                }
            });
        } else {
            addContent(moduleCounter);
        }
    }

    // Remove module
    $(document).on('click', '.remove-module', function() {
        if (confirm('Are you sure you want to remove this module?')) {
            $(this).closest('.module').remove();
            updateModuleNumbers();
        }
    });

    // Toggle module collapse/expand
    $(document).on('click', '.btn-toggle-module', function() {
        const moduleId = $(this).data('module-id');
        const moduleBody = $('#module-body-' + moduleId);
        const icon = $(this).find('.toggle-icon');

        moduleBody.slideToggle(300);

        if ($(this).attr('aria-expanded') === 'true') {
            $(this).attr('aria-expanded', 'false');
            icon.text('▶');
        } else {
            $(this).attr('aria-expanded', 'true');
            icon.text('▼');
        }
    });

    // Add content
    $(document).on('click', '.add-content-btn', function() {
        const moduleId = $(this).data('module-id');
        addContent(moduleId);
    });

    function addContent(moduleId, parentContainer = null, contentData = null) {
        contentCounters[moduleId]++;
        const contentId = contentCounters[moduleId];
        const nestLevel = parentContainer ? parentContainer.parents('.content-item').length : 0;

        const contentHtml = `
            <div class="content-item ${nestLevel > 0 ? 'nested-content-item' : ''}" data-content-id="${contentId}" data-nest-level="${nestLevel}">
                <div class="content-header">
                    <div class="content-header-left">
                        <span class="content-number">${nestLevel > 0 ? '└─' : ''} #${contentId}</span>
                        <strong class="content-title-label">Content ${contentId}</strong>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-secondary btn-sm add-nested-content-btn" data-module-id="${moduleId}" title="Add nested content">
                            <span class="btn-icon">📂</span> Add Nested
                        </button>
                        <button type="button" class="btn btn-danger btn-sm remove-content-btn" title="Remove this content">
                            <span class="btn-icon">🗑️</span> Remove
                        </button>
                    </div>
                </div>

                <div class="form-group">
                    <label>Content Title *</label>
                    <input type="text" class="content-title" value="${contentData ? contentData.title : ''}" required placeholder="Enter content title">
                </div>

                <div class="form-group">
                    <label>Content Type *</label>
                    <select class="content-type" required>
                        <option value="text" ${contentData && contentData.type === 'text' ? 'selected' : ''}>📄 Text</option>
                        <option value="video" ${contentData && contentData.type === 'video' ? 'selected' : ''}>🎥 Video</option>
                        <option value="document" ${contentData && contentData.type === 'document' ? 'selected' : ''}>📎 Document</option>
                        <option value="quiz" ${contentData && contentData.type === 'quiz' ? 'selected' : ''}>❓ Quiz</option>
                    </select>
                </div>

                <div class="form-group">
                    <label>Content Body</label>
                    <textarea class="content-body" rows="3" placeholder="Enter content description or text">${contentData ? contentData.body || '' : ''}</textarea>
                </div>

                <div class="form-group file-upload-group" style="display: ${contentData && (contentData.type === 'video' || contentData.type === 'document') ? 'block' : 'none'};">
                    <label>Upload File</label>
                    ${contentData && contentData.file_path ? `<p class="text-sm text-gray-600 mb-1">Current file: ${contentData.file_path.split('/').pop()}</p>` : ''}
                    <input type="file" class="content-file" accept="*/*">
                    <small class="form-text">Max size: 50MB</small>
                </div>

                <div class="nested-content"></div>
            </div>
        `;

        if (parentContainer) {
            parentContainer.find('> .nested-content').append(contentHtml);
        } else {
            $(`.contents-container[data-module-id="${moduleId}"]`).append(contentHtml);
        }

        // Add nested children if exists
        if (contentData && contentData.children && contentData.children.length > 0) {
            const addedContent = parentContainer 
                ? parentContainer.find('> .nested-content > .content-item').last()
                : $(`.contents-container[data-module-id="${moduleId}"] > .content-item`).last();
            
            contentData.children.forEach(child => {
                addContent(moduleId, addedContent, child);
            });
        }
    }

    // Add nested content
    $(document).on('click', '.add-nested-content-btn', function() {
        const moduleId = $(this).data('module-id');
        const parentContent = $(this).closest('.content-item');
        addContent(moduleId, parentContent);
    });

    // Handle content type change to show/hide file upload
    $(document).on('change', '.content-type', function() {
        const contentType = $(this).val();
        const fileUploadGroup = $(this).closest('.content-item').find('> .form-group.file-upload-group');
        const fileInput = fileUploadGroup.find('.content-file');

        if (contentType === 'video' || contentType === 'document') {
            fileUploadGroup.show();
            if (contentType === 'video') {
                fileInput.attr('accept', 'video/mp4,video/x-msvideo,video/quicktime,video/x-ms-wmv');
            } else {
                fileInput.attr('accept', '.pdf,.doc,.docx,.txt');
            }
        } else {
            fileUploadGroup.hide();
            fileInput.val('');
        }
    });

    // Remove content
    $(document).on('click', '.remove-content-btn', function() {
        const contentItem = $(this).closest('.content-item');
        const moduleContainer = contentItem.closest('.module');
        const allContents = moduleContainer.find('.content-item').length;

        if (allContents <= 1) {
            alert('Each module must have at least one content item.');
            return;
        }

        if (confirm('Are you sure you want to remove this content? All nested content will also be removed.')) {
            contentItem.remove();
        }
    });

    function updateModuleNumbers() {
        $('.module').each(function(index) {
            $(this).find('.module-title').text('Module ' + (index + 1));
        });
    }

    // Form submission
    $('#courseForm').submit(function(e) {
        e.preventDefault();

        // Validate form
        if (!validateForm()) {
            return;
        }

        const submitBtn = $('#submitBtn');
        submitBtn.prop('disabled', true);
        submitBtn.html('<span class="loading"></span> Updating...');

        const formData = collectFormDataWithFiles();

        $.ajax({
            url: '/api/courses/' + courseId,
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-HTTP-Method-Override': 'PUT'
            },
            success: function(response) {
                showMessage('Course updated successfully! Redirecting...', 'success');
                setTimeout(() => {
                    window.location.href = '/courses/' + courseId;
                }, 1500);
            },
            error: function(xhr) {
                let errorMessage = 'Failed to update course. Please try again.';
                if (xhr.responseJSON && xhr.responseJSON.errors) {
                    errorMessage = Object.values(xhr.responseJSON.errors).flat().join('<br>');
                } else if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                showMessage(errorMessage, 'error');
                submitBtn.prop('disabled', false);
                submitBtn.html('<span class="btn-text">Update Course</span>');
            }
        });
    });

    function validateForm() {
        const title = $('#courseTitle').val().trim();
        const modules = $('.module').length;

        if (!title) {
            showMessage('Course title is required.', 'error');
            return false;
        }

        if (modules === 0) {
            showMessage('At least one module is required.', 'error');
            return false;
        }

        // Validate each module
        let valid = true;
        $('.module').each(function() {
            const moduleTitle = $(this).find('input[name*="[title]"]').val().trim();
            const contents = $(this).find('.content-item').length;

            if (!moduleTitle) {
                showMessage('All module titles are required.', 'error');
                valid = false;
                return false;
            }

            if (contents === 0) {
                showMessage('Each module must have at least one content item.', 'error');
                valid = false;
                return false;
            }
        });

        return valid;
    }

    function collectFormDataWithFiles() {
        const formData = new FormData();

        // Add course fields
        formData.append('title', $('#courseTitle').val().trim());
        formData.append('description', $('#courseDescription').val().trim());
        formData.append('category', $('#courseCategory').val().trim());

        // Add course files
        const thumbnailFile = $('#courseThumbnail')[0].files[0];
        if (thumbnailFile) {
            formData.append('thumbnail', thumbnailFile);
        }

        const videoFile = $('#courseFeatureVideo')[0].files[0];
        if (videoFile) {
            formData.append('feature_video', videoFile);
        }

        // Collect modules data
        const modulesData = [];
        $('.module').each(function() {
            const moduleData = {
                title: $(this).find('input[name*="[title]"]').val().trim(),
                description: $(this).find('textarea[name*="[description]"]').val().trim(),
                contents: []
            };

            // Collect top-level contents
            $(this).find('.contents-container > .content-item').each(function() {
                moduleData.contents.push(collectContentDataWithFiles($(this), formData));
            });

            modulesData.push(moduleData);
        });

        formData.append('modules', JSON.stringify(modulesData));

        return formData;
    }

    function collectContentDataWithFiles(contentElement, formData) {
        const contentData = {
            title: contentElement.find('> .form-group > .content-title').val().trim(),
            type: contentElement.find('> .form-group > .content-type').val(),
            body: contentElement.find('> .form-group > .content-body').val().trim(),
            children: []
        };

        // Handle file upload if present
        const fileInput = contentElement.find('> .form-group.file-upload-group > .content-file')[0];
        if (fileInput && fileInput.files.length > 0) {
            // Add file to FormData with a unique key
            const contentPath = getContentPath(contentElement);
            const fileKey = `content_file_${contentPath}`;
            formData.append(fileKey, fileInput.files[0]);
            // Mark this content as having a file
            contentData.hasFile = true;
            contentData.fileKey = fileKey;
        }

        // Collect nested contents
        contentElement.find('> .nested-content > .content-item').each(function() {
            contentData.children.push(collectContentDataWithFiles($(this), formData));
        });

        return contentData;
    }

    // Helper function to get a unique path for a content item
    function getContentPath(contentElement) {
        const path = [];
        let current = contentElement;

        while (current.length > 0 && current.hasClass('content-item')) {
            const index = current.index();
            path.unshift(index);
            current = current.parent().closest('.content-item');
        }

        // Also include module index
        const moduleContainer = contentElement.closest('.contents-container');
        const moduleIndex = $('.contents-container').index(moduleContainer);
        path.unshift(moduleIndex);

        return path.join('_');
    }

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

    // Load existing course data
    if (courseData && courseData.modules && courseData.modules.length > 0) {
        courseData.modules.forEach(module => {
            addModule(module);
        });
    } else {
        // Initialize with one module if no existing data
        addModule();
    }
});
</script>
</x-slot>
</x-app-layout>
