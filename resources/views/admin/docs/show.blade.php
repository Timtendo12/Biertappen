@php use Illuminate\Mail\Markdown; @endphp
<x-layoutadmin header="docs/{{$page}}.md">
    <div class="format min-w-full">
        {{Markdown::parse($doc)}}
    </div>
</x-layoutadmin>
