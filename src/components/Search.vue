<template>
  <div class="k-search" role="search" @click="close">
    <div class="k-search-box" @click.stop>
      <div class="k-search-input">
        <input
          ref="input"
          v-model="q"
          :placeholder="$t('search') + ' …'"
          aria-label="$t('search')"
          type="text"
          @keydown.down.prevent="down"
          @keydown.up.prevent="up"
          @keydown.tab.prevent="tab"
          @keydown.enter="enter"
          @keydown.esc="close"
        >
        <k-button
          :tooltip="$t('close')"
          class="k-search-close"
          icon="cancel"
          @click="close"
        />
      </div>
      <ul>
        <li
          v-for="(item, itemIndex) in items.slice(0, 10)"
          :key="item.id"
          :data-selected="selected === itemIndex"
          @mouseover="selected = itemIndex"
        >
          <k-link :to="item.link" @click="click(itemIndex)">
            <k-image v-if="thumb(item.image)" v-bind="thumb(item.image)" />
            <k-icon v-else v-bind="item.icon" />
            <div>
              <strong>{{ item.title }}</strong>
              <small>{{ item.info }}</small>
            </div>
          </k-link>
        </li>
      </ul>
      <div v-if="pagination.total > items.length" class="k-search-more">
        <k-button
          :tooltip="$t('all')"
          icon="expand"
          @click="onAll"
        >
          {{ $t("algolia.all") }}
        </k-button>
      </div>
    </div>
  </div>
</template>

<script>
import thumb from "./../mixins/thumb";

export default {
  extends: "k-search",
  mixins: [thumb],
  data() {
    return {
      items: [],
      q: null,
      selected: -1,
      pagination: {
        page: 1
      }
    }
  },
  methods: {
    search(query) {
      this.$api.get("algolia", {
        q: query,
        page: this.pagination.page,
        select: [
          "id",
          "title",
          "email",
          "name",
          "filename",
          "link",
          "avatar",
          "panelIcon",
          "panelImage"
        ]
      }).then(response => {
        this.items = response.data.map(data => {
          let item;

          if (data.hasOwnProperty("email")) {
            item = this.map_users(data);
            item.icon = { type: "user" };
            item.image = data.avatar ? data.avatar : null;

          } else if (data.hasOwnProperty("filename")) {
            item = this.map_files(data);
            item.icon = data.panelIcon;
            item.image = data.panelImage;

          } else {
            item = this.map_pages(data);
            item.icon = data.panelIcon;
          }

          return item;
        });
        this.pagination = response.pagination;
        this.selected = -1;
      }).catch((e) => {
        console.log(e)
        this.items = [];
        this.selected = -1;
      });
    },
    onAll() {
      this.$router.push({ path: "/plugins/algolia", query: { q: this.q }})
      this.$store.dispatch("search", false);
    }
  }
};
</script>

<style lang="scss">
.k-search li .k-link {
  display: flex;
}
.k-search li .k-icon {
  margin-right: 1rem;
  width: 32px;
  height: 32px;
}
.k-search li .k-image {
  margin-right: 1rem;
  width: 32px;
  height: 32px;
  object-fit: cover;
}
.k-search-more {
  background: #efefef;
  display: flex;
  justify-content: center;
  padding: .75rem;
}
</style>
