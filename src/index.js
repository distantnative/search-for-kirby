import Search from "./components/Search.vue";
import Section from "./components/Section.vue";

panel.plugin("distantnative/search-for-kirby", {
  sections: {
    search: Section
  },
  components: {
    "k-search": Search
  }
});
