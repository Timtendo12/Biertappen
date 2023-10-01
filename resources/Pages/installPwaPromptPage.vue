<template>
  <div>
    <!--modals-->
    <menu_modal type="iphone" :show="showIphoneModal" @updateModal="updateModal">
      <div class="flex flex-col items-center gap-3">
        <h1 class="font-bold text-xl text-center">Hoe installeer ik Biertappen op mijn IPhone?</h1>
        <div>
          <ol class="flex flex-col items-center justify-center gap-2 text-xs">
            <li><span class="font-bold">1. Voeg toe aan beginscherm:</span> Tik onderaan het scherm op het deelpictogram <span class="italic">(het vierkantje met een pijl omhoog)</span>. Hiermee open je het deelmenu.</li>
            <li><span class="font-bold">2. Blader omlaag:</span> Blader omlaag in het deelmenu en zoek naar de optie "Zet op beginscherm" <span class="italic">(of iets vergelijkbaars, afhankelijk van je iOS-versie)</span>. Tik hierop.</li>
            <li><span class="font-bold">3. Aanpassen:</span> Je kunt nu een naam voor de PWA kiezen en eventueel een pictogram selecteren voordat je op "Voeg toe" tikt.</li>
            <li><span class="font-bold">4. Gereed:</span> De PWA-versie van "Biertappen" is nu toegevoegd aan je beginscherm als een app-achtig pictogram. Je kunt er nu op tikken om de app te openen.</li>
          </ol>
        </div>
        </div>
    </menu_modal>
    <menu_modal type="android" :show="showAndroidModal" @updateModal="updateModal">
      <div class="flex flex-col items-center gap-3">
        <h1 class="font-bold text-xl text-center">Hoe installeer ik Biertappen op mijn Android?</h1>
        <div>
          <ol class="flex flex-col items-center justify-center gap-2 text-xs">
            <li><span class="font-bold">1. Open Chrome:</span> Ontgrendel je Android-telefoon en open de Google Chrome-browser.</li>
            <li><span class="font-bold">2. Bezoek de website:</span> Typ de "https://www.biertappen.net" in de adresbalk van Chrome.</li>
            <li><span class="font-bold">3. Voeg toe aan startscherm:</span> Tik op het menupictogram (meestal drie verticale stippen) in de rechterbovenhoek van Chrome om het menu te openen.</li>
            <li><span class="font-bold">4. Selecteer "Toevoegen aan startscherm":</span> Scroll omlaag in het menu en zoek de optie "Toevoegen aan startscherm" (dit kan ook "Toevoegen aan startscherm" of iets vergelijkbaars zijn). Tik hierop.</li>
            <li><span class="font-bold">5. Geef een naam op:</span> Geef de PWA een naam voor het startscherm (bijvoorbeeld "Biertappen") en tik op "Toevoegen".</li>
            <li><span class="font-bold">6. Gereed:</span> De PWA-versie van "Biertappen" is nu toegevoegd aan je startscherm als een app-pictogram. Je kunt er nu op tikken om snel toegang te krijgen tot de website zonder de browser te openen.</li>
          </ol>

        </div>
      </div>
    </menu_modal>
    <menu_modal type="appstore" :show="showAppStoreModal" @updateModal="updateModal">
      <div class="flex flex-col items-center gap-2">
        <h1 class="font-bold text-2xl">Waarom niet de appstore?</h1>
        <p class="text-justify text-xs">
          <span class="font-bold">Dat kost zo klere veel geld.</span> Gelukkig biedt de app de mogelijkheid om een biertje te doneren. Dit is niet verplicht, maar wel erg vriendelijk. En wie weet, na genoeg biertjes komt er misschien een versie beschikbaar in de app store!
        </p>
      </div>
    </menu_modal>
    <!--modals-->
    <div id="main" class="h-full w-screen">
      <div id="bg" class="absolute w-full h-screen bg-cover -z-10" style="background-image: url('assets/img/bg.jpg')"></div>
      <div id="menuLayer" class="flex flex-col align-middle h-screen justify-between gap-2">
        <img draggable="false" src="/assets/img/BierTappenLow.png" alt="Logo" class="drop-shadow-2xl drop-shadow w-72 p-2 mt-4 mx-auto">
        <div class="flex flex-col items-center justify-center gap-5 px-4">
          <h1 class="text-2xl text-white font-bold text-center">Je bent er bijna!</h1>
          <p class="text-xs text-white text-center">Het enige wat je nog hoeft te doen, is de app op je telefoon installeren. Hieronder vind je instructies om dit te doen.</p>
          <menu_button text="Iphone" v-on:click="updateModal('iphone', true)" />
          <menu_button text="Android" v-on:click="updateModal('android', true)" />
          <menu_button text="App store?" v-on:click="updateModal('appstore', true)" />
        </div>
        <div id="footer" class="text-white flex flex-row font-dklongreach text-xs justify-around">
          <p>Gemaakt door een hele charmante jongenman.</p>
          <a href="https://github.com/Timtendo12/Biertappen" target="_blank">Github (OS)</a>
          <a href="https://twitter.com/Handzeepje12" target="_blank">X</a>
        </div>
      </div>
    </div>
  </div>
</template>
<script>
import Menu_button from "./components/menu/menu_button.vue";
import Menu_modal from "./components/modal/menu_modal.vue";
export default {
  name: 'installPwaPromptPage',
  components: {Menu_button, Menu_modal},
  data() {
    return {
      showIphoneModal: false,
      showAndroidModal: false,
      showAppStoreModal: false,
    }
  },
  methods: {
    updateModal(type, show) {
      console.log('hideModal app', type, show);
      switch (type) {
        case 'iphone':
          this.showIphoneModal = show;
          break;
        case 'android':
          this.showAndroidModal = show;
          break;
        case 'appstore':
          this.showAppStoreModal = show;
          break;
      }
    },
  }
}
</script>
