@props(['src', 'poster' => null, 'controls' => true, 'autoplay' => false, 'class' => ''])

@php
    $videoId = 'video-' . uniqid();
    // If the src is an absolute URL (http/https) keep it. Otherwise route through our range-aware streaming endpoint.
    $isAbsolute = str_starts_with($src, 'http://') || str_starts_with($src, 'https://');
    $fullSrc = $isAbsolute ? $src : route('videos.stream', ['path' => $src]);
@endphp

<div class="video-player-container {{ $class }}" data-video-id="{{ $videoId }}">
    <video 
        id="{{ $videoId }}" 
        class="video-player rounded-lg shadow-md w-full"
        {{ $controls ? 'controls' : '' }}
        {{ $autoplay ? 'autoplay' : '' }}
        {{ $poster ? 'poster=' . asset('storage/' . $poster) : '' }}
        preload="metadata"
        playsinline
    >
        <source src="{{ $fullSrc }}" type="video/mp4">
        <source src="{{ $fullSrc }}" type="video/webm">
        <source src="{{ $fullSrc }}" type="video/ogg">
        
        <!-- Fallback for browsers that don't support video tag -->
        <p class="video-fallback">
            Your browser doesn't support HTML5 video. 
            <a href="{{ $fullSrc }}" download class="text-indigo-600 hover:text-indigo-800 underline">
                Download the video
            </a> instead.
        </p>
    </video>
    
    <!-- Loading indicator -->
    <div class="video-loading absolute inset-0 items-center justify-center bg-gray-900 bg-opacity-50 rounded-lg" style="display: none;">
        <div class="text-white text-center">
            <div class="loading-spinner mb-2"></div>
            <p>Loading video...</p>
        </div>
    </div>
    
    <!-- Error message -->
    <div class="video-error mt-2 p-3 bg-red-50 border border-red-200 rounded text-red-700 text-sm" style="display: none;">
        <strong>Error loading video:</strong> 
        <span class="error-message"></span>
        <a href="{{ $fullSrc }}" download class="ml-2 underline">Download instead</a>
    </div>
</div>

<style>
.video-player-container {
    position: relative;
}

.video-player {
    max-width: 100%;
    height: auto;
    background-color: #000;
}

.video-loading .loading-spinner {
    width: 40px;
    height: 40px;
    border: 4px solid rgba(255, 255, 255, 0.3);
    border-top-color: #fff;
    border-radius: 50%;
    animation: spin 0.8s linear infinite;
    margin: 0 auto;
}

/* Ensure the loading overlay doesn't block pointer events when hidden. When visible (display:flex) we allow pointer events. */
.video-loading {
    pointer-events: none;
}
.video-loading[style*="display: flex"],
.video-loading[style*="display: block"] {
    pointer-events: auto;
}

@keyframes spin {
    to { transform: rotate(360deg); }
}

.video-fallback {
    padding: 2rem;
    background: #1f2937;
    color: #fff;
    text-align: center;
    border-radius: 0.5rem;
}
</style>

<script>
(function() {
    const videoId = '{{ $videoId }}';
    const videoElement = document.getElementById(videoId);
    const container = videoElement.closest('.video-player-container');
    const loadingIndicator = container.querySelector('.video-loading');
    const errorContainer = container.querySelector('.video-error');
    const errorMessage = errorContainer.querySelector('.error-message');
    
    // Show loading indicator
    videoElement.addEventListener('loadstart', function() {
        if (loadingIndicator) {
            loadingIndicator.style.display = 'flex';
        }
    });
    
    // Hide loading indicator when video can play
    videoElement.addEventListener('canplay', function() {
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
    });
    
    // Handle video errors
    videoElement.addEventListener('error', function(e) {
        if (loadingIndicator) {
            loadingIndicator.style.display = 'none';
        }
        if (errorContainer && errorMessage) {
            errorContainer.style.display = 'block';
            
            let errorText = 'Unable to load video file.';
            if (videoElement.error) {
                switch (videoElement.error.code) {
                    case 1:
                        errorText = 'Video loading aborted.';
                        break;
                    case 2:
                        errorText = 'Network error while loading video.';
                        break;
                    case 3:
                        errorText = 'Video decoding failed.';
                        break;
                    case 4:
                        errorText = 'Video format not supported.';
                        break;
                }
            }
            errorMessage.textContent = errorText;
        }
    });
    
    // Lazy loading optimization
    if ('IntersectionObserver' in window) {
        const observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    videoElement.load();
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.25 });
        
        observer.observe(videoElement);
    } else {
        // Fallback for browsers without IntersectionObserver
        videoElement.load();
    }
})();
</script>
