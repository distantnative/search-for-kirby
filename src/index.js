import Section from "./components/Section.vue";
import Modal from "./components/Modal.vue";

panel.plugin("distantnative/search-for-kirby", {
  sections: {
    search: Section
  },
  components: {
    "k-search": Modal
  }
});
