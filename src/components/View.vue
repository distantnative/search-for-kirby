<template>
  <k-view class="k-search-view">
    <k-header @click.native="$store.dispatch('search', true)">
      {{ $t("view.search") }}:
      <span ref="input" class="k-search-view-term">{{ q }}</span>
    </k-header>

    <k-collection layout="list" :items="listItems" :pagination="pagination" @paginate="paginate" />

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

<style lang="scss">
.k-search-view-term {
  font-weight: 300;
}
</style>
