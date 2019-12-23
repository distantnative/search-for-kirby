<template>
  <k-view class="k-algolia-view">
    <k-header>
      {{ $t("view.algolia") }}: {{ q }}
    </k-header>

    <k-collection layout="list" :items="listItems" :pagination="pagination" @paginate="paginate" />

    <input type="hidden" ref="input" />
  </k-view>
</template>

<script>
import Modal from "./Modal";

export default {
  extends: Modal,
  watch: {
    "$route.query.q": {
      handler(query) {
        this.q = query;
      },
      immediate: true
    }
  },
  computed: {
    listItems() {
      return this.items.map(item => {
        return {
          ...item,
          text: item.title
        }
      })
    }
  },
  methods: {
    paginate(pagination) {
      this.pagination = pagination;
      this.search(this.q);
    }
  }
}
</script>
