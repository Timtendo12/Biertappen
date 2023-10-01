import {defineStore} from "pinia";

export const usePlayersStore = defineStore('players',{
  state: () => ({players: [],}),
  actions: {
    addPlayer(player) {
      console.log(player, this.players);
      this.players.push(player);
    },
    removePlayer(player) {
        this.players.splice(this.players.indexOf(player), 1);
    },
    updatePlayer(id, player) {
        this.players[this.players.findIndex((p) => p.id === id)] = player;
    }
  },
  getters: {
    getPlayers: () => (state) => state.players,
    getPlayer: () => (state, id) => state.players.find((p) => p.id === id),
    getPlayerFromUsername: () => (state, username) => state.players.find((p) => p.username === username),
    getRandomPlayer: () => (state) => state.players[Math.floor(Math.random() * this.players.length)],
    getPlayersCount: () => (state) => state.players.length,
  }
});
