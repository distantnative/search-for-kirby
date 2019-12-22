import Section from "./components/Section.vue";
import Search from "./components/Search.vue";
import View from "./components/View.vue";

panel.plugin("getkirby/algolia", {
  sections: {
    algolia: Section
  },
  views: {
    algolia: {
      menu: false,
      icon: "search",
      component: View
    }
  },
  components: {
    "k-search": Search
  }
});
