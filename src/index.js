import Section from "./components/Section.vue";
import Modal from "./components/Modal.vue";
import View from "./components/View.vue";

panel.plugin("distantnative/search-for-kirby", {
  sections: {
    search: Section
  },
  views: {
    search: {
      menu: false,
      icon: "search",
      component: View
    }
  },
  components: {
    "k-search": Modal
  }
});
