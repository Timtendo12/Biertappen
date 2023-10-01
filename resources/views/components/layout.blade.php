@props(['bgAnim' => false])
<!doctype html>
<html lang="en" class="bg-blue-950">
<head>
    <meta charset="UTF-8">
    <link rel="manifest" href="manifest.json">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>Biertappen</title>
    @vite(['resources/css/app.css','resources/js/app.js'])
</head>
<body>
{{--check if we are not on the admin route--}}
@if(!str_contains(request()->path(), 'admin'))
    @desktop
    <p>Biertappen werkt helaas niet op computers en laptops.</p>
    @enddesktop

    @handheld
    {{$slot}}
    @endhandheld
@else
    {{$slot}}
@endif
<script>
    // preventing landscape mode to fuck everything up!
    window.screen.orientation.lock('portrait-primary').then(() => {
        console.log("Orientation locked to potrait primary")
    })
    .catch((error) => {
        console.error("JS Fucked up somewhere. I could not lock the screen. Are you playing this on some sorta pc using the inspector's mobile view shit? ICU ICU.", error)
    })
</script>
</body>
</html>
