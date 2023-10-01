<template>
  <app_layout>
    <!--modals-->
    <menu_modal type="add_player" :show="showAddPlayerModal" @updateModal="updateModal">
      <div class="flex flex-col items-center gap-2">
        <div id="playersList" class="bg-slate-700 w-full rounded-xl transition-all">

          <div v-if="players.length" v-for="(player, i) in players" class="transition-all">
            <div class="flex flex-row items-center justify-between px-2 py-1 transition-all">
              <p class="text-white italic">{{(i+1)}}. {{player.name}}</p>
              <a v-on:click="removePlayer(player.id)" class="text-red-400 text-sm">Verwijder</a>
            </div>
          </div>

          <div v-else>
            <div class="flex flex-row items-center justify-center px-2 py-1">
              <p class="text-white"><span class="italic">Womp Womp</span> geen spelers gevonden</p>
            </div>
          </div>
        </div>

        <div id="playerInput" class="flex flex-row items-center gap-2 justify-between w-full">
          <input :disabled="players.length >= 15" id="addPlayerInput" v-model="addPlayerInsert" placeholder="Voeg speler toe" class="rounded-md py-0.5 px-1">
          <button :disabled="players.length >= 15" v-on:click="addPlayer" class="transition-all py-0.5 px-1 font-semibold disabled:opacity-30 bg-blue-600 active:bg-blue-700 border-blue-800 active:border-blue-900 border-2 rounded-md text-white ">Toevoegen</button>
        </div>

        <div id="start" class="flex flex-col items-center justify-center w-full mt-5">
          <button v-on:click="startGame" :disabled="players.length <= 1" class="disabled:opacity-30 w-full py-3 bg-red-500 border-2 border-red-700 active:bg-red-600 active:border-red-800 rounded-md font-bold transition-all">Biertappen!</button>
        </div>
      </div>
    </menu_modal>
    <menu_modal type="tutorial" :show="showTutorialModal" @updateModal="updateModal">
      <div class="flex flex-col items-center gap-2">
        <h1 class="font-bold text-2xl">Hoe werkt het?</h1>
        <p class="text-justify text-xs">
          Biertappen is een digitaal drankspel dat wordt gespeeld via een smartphone-app. Het spel omvat verschillende uitdagingen en opdrachten die deelnemers moeten uitvoeren.
          Deze opdrachten kunnen variëren van het beantwoorden van grappige vragen tot het uitvoeren van fysieke uitdagingen.
          Spelers draaien aan een virtuele draaischijf in de app, en de draaischijf wijst op willekeurige wijze een speler aan om een opdracht uit te voeren.
          Het leuke aan Biertappen is dat het veel hilarische uitdagingen bevat. Afhankelijk van de instructies dienen spelers slokken drank te nemen of drankjes aan andere spelers toe te wijzen.
          Het spel is bedoeld om leuk en sociaal te zijn. Biertappen is ontworpen voor groepen vrienden (of duo's) die op zoek zijn naar een leuke activiteit om tijdens een feestje of bij het indrinken te spelen.
          Het zorgt voor lachen, interactie en kan een geweldige ijsbreker zijn, <span class="font-bold">maar houd altijd rekening met de grenzen en het welzijn van alle deelnemers... <span class="italic">of niet.</span></span> <span class="text-sm">🤷‍</span>
        </p>
      </div>
    </menu_modal>
    <menu_modal type="tikkie" :show="showTikkieModal" @updateModal="updateModal">
      <div class="flex flex-col items-center gap-2">
        <h1 class="font-bold text-2xl">Doneer een biertje.</h1>
        <p class="text-justify">
<!--          TODO: Tikkie tekst-->
          TODO: Tikkie tekst
        </p>
      </div>
    </menu_modal>
    <!--modals-->


    <div id="main" class="h-full w-screen">

      <div id="bg" class="absolute w-screen h-screen bg-cover -z-10" style="background-image: url('assets/img/bg.jpg')"></div>

      <div id="menuLayer" class="flex flex-col align-middle h-screen justify-between">
        <div></div>

        <img src="/assets/img/BierTappenLow.png" alt="Logo" class="drop-shadow-2xl w-9/12 p-2 mt-4 mx-auto">

        <div id="buttons" class="flex flex-col justify-around gap-4">
          <menu_button text="Starten" v-on:click="updateModal('add_player', true)"/>
          <menu_button text="Hoe werkt het?" v-on:click="updateModal('tutorial', true)"/>
        </div>

        <div id="button-secondary" class="flex flex-col justify-around gap-4">
          <menu_button text="Doneer een biertje." v-on:click="updateModal('tikkie', true)"/>
        </div>

        <div id="spacer">
        </div>

        <div id="footer" class="text-white flex flex-row font-dklongreach text-xs justify-around">
          <p>Gemaakt door een hele charmante jongenman.</p>
          <a href="https://github.com/Timtendo12/Biertappen" target="_blank">Github (OS)</a>
          <a href="https://twitter.com/Handzeepje12" target="_blank">X</a>
        </div>

      </div>
    </div>
  </app_layout>
</template>

<script>
import menu_button from "./components/menu/menu_button.vue";
import App_layout from "./layouts/app_layout.vue";
import Menu_modal from "./components/modal/menu_modal.vue";
import {usePlayersStore} from "../stores/players.js";
import {computed} from "vue";
export default {
  name: "App",
  components: {
    Menu_modal,
    App_layout,
    menu_button
  },
  data() {
    return {
      url: '',
      showAddPlayerModal: false,
      showTutorialModal: false,
      showTikkieModal: false,
      addPlayerInsert: '',
    }
  },
  setup() {
    let playersStore = usePlayersStore();
    let players = computed(() => playersStore.players);
    return { players, playersStore }
  },
  created() {
    this.url = window.location.href;

    this.waitForElm("#addPlayerInput").then((elm) => {
      console.log(elm);
      elm.addEventListener("keyup", event => {
        console.log(event);
        if (event.key !== "Enter") return;
        this.addPlayer();
        event.preventDefault();
      });
    });
  },
  methods: {
    waitForElm(selector) {
      return new Promise(resolve => {
        if (document.querySelector(selector)) {
          return resolve(document.querySelector(selector));
        }
        const observer = new MutationObserver(mutations => {
          if (document.querySelector(selector)) {
            observer.disconnect();
            resolve(document.querySelector(selector));
          }
        });
        observer.observe(document.body, {
          childList: true,
          subtree: true
        });
      });
      },
    addPlayer() {
      if (this.players.length >= 15) {
        return;
      }

      const name = this.addPlayerInsert;

      if (!name || name.length < 1) {
        return;
      }

      console.log(name);
      let player = {
        id: this.players.length,
        name: name,
      }
      this.playersStore.addPlayer(player);
      this.addPlayerInsert = '';
    },
    removePlayer(id) {
      this.playersStore.removePlayer(id);
    },
    updateModal(type, show) {
      console.log('hideModal app', type, show);
      switch (type) {
        case 'add_player':
          this.showAddPlayerModal = show;
          break;
        case 'tutorial':
          this.showTutorialModal = show;
          break;
        case 'tikkie':
          this.showTikkieModal = show;
          break;
      }
    },
    startGame() {
      this.updateModal('add_player', false);


    }
  }
}
</script>
