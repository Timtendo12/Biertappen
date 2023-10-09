<x-layoutadmin header="{{$pack->name}}">
    <x-tasks.taskmodal :tasks="$tasks" :pack_id="$pack->id" />
    <form method="post" enctype="multipart/form-data">
        @method('PUT')
        @csrf
        <div class="border-b border-gray-900/10 pb-12">
            <h2 class="text-base font-semibold leading-7 text-gray-900">Opdrachten packs</h2>
            <p class="mt-1 text-sm leading-6 text-gray-600">Packs zijn collecties aan opdrachten.</p>

            <div class="mt-10 grid grid-cols-1 gap-x-6 gap-y-8 sm:grid-cols-6">
                <div class="sm:col-span-3">
                    <label for="name" class="block text-sm font-medium leading-6 text-gray-900">Pack naam</label>
                    <div class="mt-2">
                        <input type="text" value="{{$pack->name}}" name="name" id="name" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">
                    </div>
                </div>

                <div class="col-span-full">
                    <label for="description" class="block text-sm font-medium leading-6 text-gray-900">Omschrijving</label>
                    <div class="mt-2">
                        <textarea id="description" name="description" rows="3" style="resize: none" class="block w-full rounded-md border-0 py-1.5 text-gray-900 shadow-sm ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-inset focus:ring-indigo-600 sm:text-sm sm:leading-6">{{$pack->description}}</textarea>
                    </div>
                </div>

                <div class="col-span-full">
                    <label for="cover-photo" class="flex flex-row items-center block text-sm font-medium leading-6 text-gray-900">Cover photo
                        <span class="font-normal ml-2">
                            <a target="_blank" class="flex flex-row items-center text-indigo-500" href="{{$pack->image}}">{{$pack->image}}
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-4 h-4 ml-0.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 013.182 0l5.159 5.159m-1.5-1.5l1.409-1.409a2.25 2.25 0 013.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 001.5-1.5V6a1.5 1.5 0 00-1.5-1.5H3.75A1.5 1.5 0 002.25 6v12a1.5 1.5 0 001.5 1.5zm10.5-11.25h.008v.008h-.008V8.25zm.375 0a.375.375 0 11-.75 0 .375.375 0 01.75 0z" />
                                </svg>
                            </a>
                        </span>
                    </label>
                    <div class="mt-2 flex bg-white justify-center rounded-lg border border-dashed border-gray-900/25 px-6 py-10">
                        <div class="text-center">
                            <svg class="mx-auto h-12 w-12 text-gray-300" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd" d="M1.5 6a2.25 2.25 0 012.25-2.25h16.5A2.25 2.25 0 0122.5 6v12a2.25 2.25 0 01-2.25 2.25H3.75A2.25 2.25 0 011.5 18V6zM3 16.06V18c0 .414.336.75.75.75h16.5A.75.75 0 0021 18v-1.94l-2.69-2.689a1.5 1.5 0 00-2.12 0l-.88.879.97.97a.75.75 0 11-1.06 1.06l-5.16-5.159a1.5 1.5 0 00-2.12 0L3 16.061zm10.125-7.81a1.125 1.125 0 112.25 0 1.125 1.125 0 01-2.25 0z" clip-rule="evenodd" />
                            </svg>
                            <div class="mt-4 flex text-sm leading-6 text-gray-600">
                                <label for="image" class="relative cursor-pointer rounded-md bg-white font-semibold text-indigo-600 focus-within:outline-none focus-within:ring-2 focus-within:ring-indigo-600 focus-within:ring-offset-2 hover:text-indigo-500">
                                    <span id="file">Upload een foto</span>
                                    <input id="image" name="image" type="file"  accept="image/png, image/jpeg" class="sr-only">
                                </label>
                                <p id="drag" class="pl-1">of sleep hier</p>
                                <script>
                                    const inputElement = document.getElementById("image");
                                    const dragElement = document.getElementById("drag");
                                    const fileElement = document.getElementById("file");

                                    inputElement.addEventListener("change", handleFiles, false);
                                    function handleFiles() {
                                        const fileList = this.files;
                                        console.log(fileList);

                                        fileElement.innerText = fileList[0].name;
                                        fileElement.style.textAlign = "center";
                                        dragElement.innerHTML = "";
                                    }
                                </script>
                            </div>
                            <p class="text-xs leading-5 text-gray-600">PNG, JPG tot 512KB</p>
                        </div>
                    </div>
                </div>

                <div class="space-y-10">
                    <fieldset>
                        <legend class="text-sm font-semibold leading-6 text-gray-900">Zichtbaar?</legend>
                        <div class="mt-1 space-y-6">
                            <div class="relative flex gap-x-3">
                                <div class="flex h-6 items-center">
                                    <input id="active" name="is_active" @checked($pack->is_active) value="true" type="checkbox" class="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-600">
                                </div>
                                <div class="text-sm leading-6">
                                    <label for="active" class="text-gray-900">Actief</label>
                                </div>
                            </div>
                        </div>
                    </fieldset>
                </div>
            </div>
        </div>
        <div class="mt-6 flex items-center justify-end gap-x-6">
            <a href="/admin/packs" class="text-sm font-semibold w-fit leading-6 text-gray-900">Annuleer</a>
            <button type="submit" class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white shadow-sm hover:bg-indigo-500 focus-visible:outline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-indigo-600">Opslaan</button>
        </div>
    </form>
    <x-packs.pack-table :tasks="$tasks"/>
    <x-words.word-table :words="$pack->wordList->words()->paginate(10)"/>
    <x-wyr.wyr-table :wyrs="$pack->wyr()->paginate(10)"/>
</x-layoutadmin>
