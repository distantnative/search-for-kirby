<template>
  <div>
    <div class="k-section-header">
      <k-headline>Algolia</k-headline>
    </div>
    <k-button icon="wand" @click="build">
      Re-build index
    </k-button>
  </div>
</template>

<script>
export default {
  methods: {
    build() {
      this.$api.post("search");
    }
  }
}
</script>
