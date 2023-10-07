@props(['tasks', 'pack_id'])
<!-- Main modal -->
<div id="biertap-task-modal" tabindex="-1" aria-hidden="true" class="fixed top-0 left-0 right-0 z-50 hidden w-full p-4 overflow-x-hidden overflow-y-auto md:inset-0 h-[calc(100%-1rem)] max-h-full">
    <div class="relative w-full max-w-md max-h-full">
        <!-- Modal content -->
        <div class="relative bg-white rounded-lg shadow dark:bg-gray-700">
            <button type="button" class="absolute top-3 right-2.5 text-gray-400 bg-transparent hover:bg-gray-200 hover:text-gray-900 rounded-lg text-sm w-8 h-8 ml-auto inline-flex justify-center items-center dark:hover:bg-gray-600 dark:hover:text-white" data-modal-hide="biertap-task-modal">
                <svg class="w-3 h-3" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 14 14">
                    <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 1 6 6m0 0 6 6M7 7l6-6M7 7l-6 6"/>
                </svg>
                <span class="sr-only">Close modal</span>
            </button>
            <div class="px-6 py-6 lg:px-8">
                <h3 id="header" class="mb-4 text-xl font-medium text-gray-900 dark:text-white">Task aanmaken</h3>
                <form id="form" class="space-y-6" method="post" action="/admin/tasks">
                    @csrf
                    <input id="packid" name="pack_id" type="hidden" value="{{$pack_id}}">
                    <div>
                        <label for="task" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Opdracht</label>
                        <textarea type="text" name="task" id="task" rows="2" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white resize-none" required></textarea>
                    </div>
                    <div class="flex flex-row items-center justify-between w-full gap-4">
                        <div class="flex flex-col justify-center">
                            <label for="player" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Spelers</label>
                            <input type="number" min="0" max="15" name="players" id="players" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" required>
                        </div>
                        <div class="flex flex-col justify-center">
                            <label for="duration" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Duratie</label>
                            <input type="number" value="0" min="0" name="duration" id="duration" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" required>
                        </div>
                        <div class="flex flex-col justify-center w-1/2">
                            <label for="player" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Type</label>
                            <select name="type" id="type" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white">
                                <option value="normal">Normaal</option>
                                <option value="virus">Virus</option>
                                <option value="thirty_seconds">Thirty Seconds</option>
                                <option value="wyr">Would You Rather</option>
                            </select>
                        </div>
                    </div>
                    <div class="flex flex-row items-center justify-between w-full gap-4">
                        <div class="flex flex-col justify-center">
                            <label for="min_sips" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Minimum slokjes</label>
                            <input type="number" value="0" min="0" name="min_sips" id="min_sips" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" required>
                        </div>
                        <div class="flex flex-col justify-center">
                            <label for="max_sips" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Maximum slokjes</label>
                            <input type="number" min="0" name="max_sips" id="max_sips" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block w-full p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" required>
                        </div>
                        <div class="flex flex-col justify-center">
                            <label for="chug" class="block mb-2 text-sm font-medium text-gray-900 dark:text-white">Atje?</label>
                            <input type="checkbox" name="chug" id="max_sips" class="bg-gray-50 border border-gray-300 text-gray-900 text-sm rounded-lg focus:ring-blue-500 focus:border-blue-500 block p-2.5 dark:bg-gray-600 dark:border-gray-500 dark:placeholder-gray-400 dark:text-white" required>
                        </div>
                    </div>
                    <div class="flex flex-row justify-center w-full gap-4">
                        <button type="submit" class="w-full text-white bg-blue-700 hover:bg-blue-800 focus:ring-4 focus:outline-none focus:ring-blue-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-blue-600 dark:hover:bg-blue-700 dark:focus:ring-blue-800">Opslaan</button>
                        <button type="button" class="w-full text-white bg-red-700 hover:bg-red-800 focus:ring-4 focus:outline-none focus:ring-red-300 font-medium rounded-lg text-sm px-5 py-2.5 text-center dark:bg-red-600 dark:hover:bg-red-700 dark:focus:ring-red-800">Verwijder</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script>
    function setModalTask(uuid= null) {
        let taskInput = document.getElementById('task');
        let playerInput = document.getElementById('players');
        let typeInput = document.getElementById('type');
        let durationInput = document.getElementById('duration');
        let header = document.getElementById('header');
        let form = document.getElementById('form');
        let packid = document.getElementById('packid');


        let tasks = @json($tasks->items());

        let task = tasks.find(task => task.uuid === uuid);
        if(!task)
            task = {'id': null, 'task': '', 'players': '', 'type': ''};
        console.log(tasks);
        console.log(task);
        sessionStorage.setItem('task', JSON.stringify(task));

        //change form action url
        form.action = `/admin/tasks/${task.id}`;

        let methodElement = document.createElement('input');
        methodElement.type = 'hidden';
        methodElement.name = '_method';
        methodElement.value = 'PUT';

        form.appendChild(methodElement);

        // remove packid input
        packid.remove();

        // fill in the inputs
        header.innerText = "Task bewerken";
        taskInput.value = task.task;
        playerInput.value = task.players;
        typeInput.value = task.type;
        durationInput.value = task.duration;
    }
</script>
