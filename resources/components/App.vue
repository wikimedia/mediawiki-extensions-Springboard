<template>
	<div class="ext-wikitweak-app">
		<cdx-tabs v-model:active="currentTab">
			<cdx-tab
				v-for="tab in tabs"
				:key="tab.name"
				class="ext-wikitweak-app__tab"
				:name="tab.name"
				:label="tab.label"
			>
				<component :is="tab.component"></component>
			</cdx-tab>
		</cdx-tabs>
	</div>
</template>

<script>
const { ref } = require( 'vue' );
const { CdxTabs, CdxTab } = require( '../codex.js' );
const Extensions = require( './Extensions.vue' );
const Skins = require( './Skins.vue' );

// @vue/component
module.exports = {
	name: 'App',
	components: {
		CdxTabs,
		CdxTab
	},
	setup() {
		const currentTab = ref('extensions');
		const tabs = [
			{
				name: 'extensions',
				label: mw.msg( 'wikitweak-extensions-tab-name' ),
				component: Extensions,
                active: true
			},
			{
				name: 'skins',
				label: mw.msg( 'wikitweak-skins-tab-name' ),
				component: Skins
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

.ext-wikitweak-app {
	&__tab {
		padding: @spacing-100;
	}
}
</style>
