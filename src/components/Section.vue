<template>
  <div>
    <div class="k-section-header">
      <k-headline>{{ headline }}</k-headline>
    </div>
    <k-button icon="refresh" :disabled="isProcessing" @click="build">
      {{ text }}
    </k-button>
  </div>
</template>

<script>
export default {
  data() {
    return {
      headline: "",
      isProcessing: false
    }
  },
  computed: {
    text() {
      let text = this.$t("search.build");

      if (this.isProcessing) {
        text += "…";
      }

      return text;
    }
  },
  async created() {
    const response = await this.load();
    this.headline = response.headline;
  },
  methods: {
    async build() {
      this.isProcessing = true;

      try {
        await this.$api.post("search");
        this.$store.dispatch("notification/success", this.$t("search.built"));

      } catch (error) {
        console.error(e);

      } finally {
        this.isProcessing = false;
      }
    }
  }
}
</script>
