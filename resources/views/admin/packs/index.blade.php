<x-layoutadmin header="Packs">
    <h1 class="text-4xl font-bold mb-5">Active packs:</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 h-full w-full gap-2 mb-10">
        <a href="/admin/packs/create" id="pack-create" class="rounded-md w-72 h-32 hover:cursor-pointer" style="background-image: url('/storage/packs/create.png')">
            <div class="absolute text-white px-4 pt-5 pb-3 flex flex-col w-72 h-32 justify-between">
                <div class="flex flex-row items-center gap-1">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-6 h-6">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v6m3-3H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <h1 class="font-bold text-xl">Pack aanmaken.</h1>
                </div>
                <p class="italic text-xs overflow-ellipsis overflow-hidden">Maak een extra pack aan.</p>
                <div class="text-xs flex flex-row justify-between">
                    <p>Daarna mag je jezelf trakteren op een biertje.</p>
                </div>
            </div>
        </a>
        @foreach($active_packs as $a_pack)
            <x-packs.pack :pack="$a_pack" />
        @endforeach
    </div>
    <h1 class="text-4xl font-bold mb-5">Inactive packs:</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 h-full w-full gap-2">
        @foreach($inactive_packs as $i_pack)
            <x-packs.pack :pack="$i_pack" />
        @endforeach
    </div>
</x-layoutadmin>
