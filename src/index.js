import Search from "./panel/Search.vue";
import Section from "./panel/Section.vue";

panel.plugin("distantnative/search-for-kirby", {
  sections: {
    search: Section
  },
  components: {
    "k-search": Search
  }
});
