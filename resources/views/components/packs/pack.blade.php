@php use App\Models\Pack;use Carbon\Carbon; @endphp
@props(['pack'=> new Pack([
    'id' => 1,
    'name' => 'Default',
    'description' => 'Dit is een standaard pack. Als je dit ziet dan heb je of geen pack meegegeven of de pack bestaat niet.',
    'creator' => 'Biertappen',
    'image' => '/storage/packs/4.png',
    'created_at' => now(),
]), 'maxChars' => 85])
<a href="/admin/packs/{{$pack->id}}" id="pack-{{$pack->id}}" class="rounded-md w-72 h-32 hover:cursor-pointer shadow shadow-2xl"
   style="background-image: url('{{$pack->image}}')">
    <div class="absolute text-white px-4 pt-5 pb-3 flex flex-col w-72 h-32 justify-between">
        <h1 class="font-bold text-xl text-shadow shadow-black">{{$pack->name}}</h1>
        <p class="italic text-xs overflow-ellipsis overflow-hidden text-shadow shadow-black	">{{(count_chars($pack->description) > $maxChars ? substr($pack->description, 0, $maxChars) . "..." : $pack->description)}}</p>
        <div class="text-xs flex flex-row justify-between">
            <p class="text-shadow shadow-black">{{$pack->creator}}</p>
            <p class="text-shadow shadow-black">{{Carbon::parse($pack->created_at)->format('Y-m-d')}}</p>
        </div>
    </div>
    {{--Debug only--}}
    <p class="text-white text-xs p-1 font-mono text-shadow shadow-black">{{$pack->uuid}}</p>
</a>
