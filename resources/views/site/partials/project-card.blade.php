@php
    /** @var \App\Models\WebsiteProject $item */
    $cover = $item->coverUrl();
    $project = $item->project;
@endphp
<a href="{{ route('site.projects.show', $project) }}" class="site-project site-reveal site-reveal--scale {{ $cover ? 'site-project--media' : '' }}" data-tilt>
    @if ($cover)
        <span class="site-project__media" style="background-image:url('{{ $cover }}')"></span>
    @endif
    <div class="site-project__body">
        <div>
            <div class="site-project__meta">
                @if ($item->status_label)
                    {{ $item->status_label }}
                @elseif ($item->location)
                    {{ $item->location }}
                @else
                    مشروع عقاري
                @endif
            </div>
            <h3>{{ $item->displayTitle() }}</h3>
            @if ($item->excerpt || $item->subtitle)
                <p class="site-project__excerpt">{{ $item->excerpt ?: $item->subtitle }}</p>
            @endif
        </div>
        <div>
            <div class="site-project__stats">
                <span>{{ (int) ($project?->properties_count ?? 0) }} عقار</span>
                <span>{{ (int) ($project?->areas_count ?? 0) }} منطقة</span>
            </div>
            <div class="site-project__link" style="margin-top:.85rem">تفاصيل المشروع ←</div>
        </div>
    </div>
</a>
