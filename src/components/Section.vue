<template>
  <div>
    <div class="k-section-header">
      <k-headline>{{ headline }}</k-headline>
    </div>
    <k-button icon="wand" @click="build">
      Re-build index
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
      this.$api.post("search");
    }
  }
}
</script>
