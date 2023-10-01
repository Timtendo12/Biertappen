<x-layout>
    <div id="main" class="h-full w-screen">

        {{--video--}}
        <video muted autoplay id="bg_anim" class="absolute w-full h-fit -z-10" style="display: none">
            <source src="/assets/video/bg_anim.mp4" type="video/mp4">
        </video>

        {{--bg--}}
        <div id="bg" class="absolute w-screen h-screen bg-cover -z-10" style="background-image: url('{{url()->current()}}/assets/img/bg.jpg')">

        </div>

        {{--menu--}}
        <div id="menuLayer" class="flex flex-col align-middle h-screen justify-between">

            <img src="/assets/img/BierTappenLow.png" alt="Logo" class="drop-shadow-2xl w-9/12 p-2 mt-4 mx-auto">

            <div id="buttons" class="flex flex-col justify-around gap-4">
                <x-menu.button text="Starten"/>
                <x-menu.button text="Hoe werkt het?"/>
            </div>

            <div id="button-secondary" class="flex flex-col justify-around gap-4">
                <x-menu.button text="Koop mij een biertje"/>
            </div>

            <div id="spacer">
            </div>

            <div id="footer" class="text-white flex flex-row font-dklongreach text-xs justify-around">
                <p>Gemaakt door een hele charmante jongen</p>
                <a href="https://github.com/Timtendo12/Biertappen" target="_blank">Github (OS)</a>
                <a href="https://twitter.com/Handzeepje12" target="_blank">X</a>
            </div>

        </div>
    </div>

    <script>

        const bgAnim = false;
        const bg_anim_dir = "/assets/video/bg_anim.mp4";
        const bg_dir = "/assets/img/bg.jpg";

        const bg = document.getElementById('bg');
        const bg_anim = document.getElementById('bg_anim');

        if (bgAnim) {
            console.log('bgAnim')
            bg.style.display = 'none';
            bg_anim.style.display = 'block';
            bg_anim.play();
            bg_anim.addEventListener('ended', () => {
                console.log('bg_anim ended')
                bg_anim.style.display = 'none';
                bg.style.display = 'block';
            });
        }
        screen.orientation.lock("portrait");
        screen.lockOrientation("portrait");
    </script>
</x-layout>
