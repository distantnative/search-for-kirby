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
  async created() {
    const response = await this.load();
    this.headline = response.headline;
  },
  methods: {
    async build() {
      try {
        await this.$api.post("search");
        this.$store.dispatch("notification/success", this.$t("search.built"));

      } catch (error) {
        console.error(e);
      }
    }
  }
}
</script>
