@if (session('success'))
    <div id="flash" class="fixed bg-green-500 text-white py-2 px-4 rounded-xl bottom-3 right-3 text-sm">
        <p>[✅] {{session('success')}}</p>
    </div>
@endif
@if (session('error'))
    <div id="flash" class="fixed bg-red-500 text-white py-2 px-4 rounded-xl bottom-3 right-3 text-sm">
        <p>[⛔️] {{session('error')}}</p>
    </div>
@endif
