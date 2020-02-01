<template>
  <div>
    <div class="k-section-header">
      <k-headline>{{ headline }}</k-headline>
    </div>
    <k-button icon="refresh" @click="build">
      {{ $t("search.build") }}
    </k-button>
  </div>
</template>

<script>
export default {
  data() {
    return {
      headline: ""
    }
  },
  created() {
    this.load().then(response => {
      this.headline = response.headline;
    });
  },
  methods: {
    build() {
      this.$api.post("search").then(() => {
        this.$store.dispatch("notification/success", this.$t("search.built"));
      }).catch(e => {
        console.error(e);
      });
    }
  }
}
</script>
