<template>
	<div class="ext-springboard-app">
		<cdx-tabs v-model:active="currentTab">
			<cdx-tab
				v-for="tab in tabs"
				:key="tab.name"
				class="ext-springboard-app__tab"
				:name="tab.name"
				:label="tab.label"
			>
				<ext-skins :type="tab.name"></ext-skins>
			</cdx-tab>
		</cdx-tabs>
	</div>
</template>

<script>
const { ref } = require( 'vue' );
const { CdxTabs, CdxTab } = require( '../codex.js' );
const ExtSkins = require( './ExtSkins.vue' );

// @vue/component
module.exports = {
	name: 'App',
	components: {
		CdxTabs,
		CdxTab,
		ExtSkins
	},
	setup() {
		const currentTab = ref('extensions');
		const tabs = [
			{
				name: 'extensions',
				label: mw.msg( 'springboard-extensions-tab-name' ),
                active: true
			},
			{
				name: 'skins',
				label: mw.msg( 'springboard-skins-tab-name' )
			}
		];

		return {
			currentTab,
			tabs
		};
	}
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';

.ext-springboard-app {
	&__tab {
		padding: @spacing-100;
	}
}
.ext-springboard-app .cdx-table__header {
  display: none;
}
</style>
