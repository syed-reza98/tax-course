<div class="content-item {{ $level > 0 ? 'nested border-l-4 border-indigo-200 bg-white' : 'bg-gray-50' }} rounded-lg p-4 {{ $level > 0 ? 'ml-6' : '' }}">
    <div class="flex justify-between items-start mb-3">
        <div class="flex-1">
            <div class="flex items-center gap-2 mb-2">
                <span class="content-type-badge content-type-{{ $content->type }} inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                    {{ $content->type === 'text' ? 'bg-blue-100 text-blue-800' : '' }}
                    {{ $content->type === 'video' ? 'bg-pink-100 text-pink-800' : '' }}
                    {{ $content->type === 'document' ? 'bg-green-100 text-green-800' : '' }}
                    {{ $content->type === 'quiz' ? 'bg-yellow-100 text-yellow-800' : '' }}">
                    @if($content->type === 'text')
                        📄 Text
                    @elseif($content->type === 'video')
                        🎥 Video
                    @elseif($content->type === 'document')
                        📎 Document
                    @elseif($content->type === 'quiz')
                        ❓ Quiz
                    @else
                        {{ ucfirst($content->type) }}
                    @endif
                </span>
                <h5 class="text-sm font-semibold text-gray-900">{{ $content->title }}</h5>
            </div>
        </div>

        @if($content->children->count() > 0)
        <button type="button" class="btn-toggle content-toggle text-gray-600 hover:text-gray-900 transition ml-2" data-target="content-children-{{ $content->id }}" aria-expanded="true">
            <span class="toggle-icon text-lg">▼</span>
        </button>
        @endif
    </div>

    @if($content->body)
    <div class="content-body mb-3">
        <p class="text-sm text-gray-600 leading-relaxed">{{ $content->body }}</p>
    </div>
    @endif

    @if($content->file_path)
    <div class="content-file mt-3">
        @if($content->type === 'video')
        <x-video-player
            :src="$content->file_path"
            class="max-w-3xl"
        />
        @elseif($content->type === 'document')
        <div class="document-link">
            <a href="{{ asset('storage/' . $content->file_path) }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                📄 View Document
            </a>
        </div>
        @else
        <div class="file-link">
            <a href="{{ asset('storage/' . $content->file_path) }}" target="_blank" class="inline-flex items-center px-3 py-2 bg-white border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition">
                📎 Download File
            </a>
        </div>
        @endif
    </div>
    @endif

    @if($content->children->count() > 0)
    <div class="nested-contents space-y-3 mt-4" id="content-children-{{ $content->id }}">
        @foreach($content->children as $childContent)
            @include('courses.partials.content-item', ['content' => $childContent, 'level' => $level + 1])
        @endforeach
    </div>
    @endif
</div>
