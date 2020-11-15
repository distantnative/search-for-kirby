<template>
  <k-overlay ref="overlay">
    <div class="k-search" role="search">
      <div class="k-search-input">
        <!-- Input -->
        <input
          ref="input"
          v-model="q"
          :placeholder="$t('search') + ' …'"
          :aria-label="$t('search')"
          :autofocus="true"
          type="text"
          @input="hasResults = true"
          @keydown.down.prevent="onDown"
          @keydown.up.prevent="onUp"
          @keydown.tab.prevent="onTab"
          @keydown.enter="onEnter"
          @keydown.esc="close"
        >
        <k-button
          :tooltip="$t('close')"
          :icon="isLoading ? 'loader' : 'cancel'"
          class="k-search-close"
          @click="close"
        />
      </div>

      <div
        v-if="q && (!hasResults || items.length)"
        class="k-search-results"
      >
        <!-- Results -->
        <ul v-if="items.length" @mouseout="selected = -1">
          <li
            v-for="(item, itemIndex) in items"
            :key="item.id"
            :data-selected="selected === itemIndex"
            @mouseover="selected = itemIndex"
          >
            <k-link :to="item.link" @click="close">
              <span class="k-search-item-image">
                <k-image
                  v-if="imageOptions(item.image)"
                  v-bind="imageOptions(item.image)"
                />
                <k-icon
                  v-else
                  v-bind="item.icon"
                />
              </span>
              <span class="k-search-item-info">
                <strong>{{ item.title }}</strong>
                <small>{{ item.info }}</small>
              </span>
            </k-link>
          </li>
        </ul>

        <!-- No index -->
        <p
          v-else-if="!hasIndex"
          class="k-search-empty k-search-index"
          @click="index"
        >
          {{ rebuild }}
        </p>

        <!-- No results -->
        <p v-else-if="!hasResults" class="k-search-empty">
          {{ $t("search.results.none") }}
        </p>
      </div>
    </div>
  </k-overlay>
</template>

<script>

export default {
  extends: "k-search",
  data() {
    return {
      hasIndex: true,
      isProcessing: false
    };
  },
  computed: {
    rebuild() {
      let text = this.$t("search.index.missing");

      if (this.isProcessing) {
        text += "…";
      }

      return text;
    }
  },
  methods: {
    async index() {
      this.isProcessing = true;

      try {
        await this.$api.post("search");
        this.$store.dispatch("notification/success", this.$t("search.index.built"));

      } catch (error) {
        console.error(e);

      } finally {
        this.isProcessing = false;
        this.search(this.q);
      }
    },
    async search(query) {
      this.isLoading = true;

      try {
        // Skip API call if query empty
        if (query === "") {
          throw new Error;
        }

        const response = await this.$api.get("search", {
          q: query,
          select: [
            "id",
            "title",
            "email",
            "name",
            "filename",
            "parent",
            "panelIcon",
            "panelImage"
          ]
        });

        this.items = response.data.map(item => {
          let data = {
            id:    item.id,
            icon:  {...item.panelIcon, back: "black", color: "#fff"},
            image: {...item.panelImage, back: "pattern", cover: true}
          }

          if (item.hasOwnProperty("email")) {
            data.title = item.name || item.email;
            data.link  = this.$api.users.link(item.id);
            data.info  = item.email;
            data.icon  = {
              back: "black",
              type: "user"
            };

          } else if (item.hasOwnProperty("filename")) {
            data.title = item.filename;
            data.link  = this.$api.files.link(
              this.$api.pages.url(item.parent.id),
              item.filename
            );
            data.info  = item.id;

          } else {
            data.title = item.title;
            data.link  = this.$api.pages.link(item.id);
            data.info  = item.id;
          }

          return data;
        });

      } catch (error) {
        if (error.key === "error.notFound") {
          this.hasIndex = false;
        }

        this.items = [];

      } finally {
        this.selected   = -1;
        this.isLoading  = false;
        this.hasResults = this.items.length > 0;
      }
    }
  }
};
</script>

<style>
.k-search-index {
  cursor: pointer;
}
</style>
