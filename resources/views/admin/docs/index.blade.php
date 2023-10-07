<x-layoutadmin header="Docs">
    <h1 class="text-3xl font-bold text-black mb-2">Beschikbare documenten</h1>
    <div class="flex flex-col"
        @foreach($docs as $doc)
        <div class="flex flex-row">
            <a href="/admin/docs/{{$doc}}/">- <span class="hover:text-blue-600">{{$doc}}</span></a>
        </div>
        @endforeach
    </div>
</x-layoutadmin>
