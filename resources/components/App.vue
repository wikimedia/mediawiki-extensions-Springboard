<template>
	<div class="ext-zest-app">
		<cdx-tabs v-model:active="currentTab">
			<cdx-tab
				v-for="tab in tabs"
				:key="tab.name"
				class="ext-zest-app__tab"
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
				label: mw.msg( 'zest-extensions-tab-name' ),
				component: Extensions,
                active: true
			},
			{
				name: 'skins',
				label: mw.msg( 'zest-skins-tab-name' ),
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

.ext-zest-app {
	&__tab {
		padding: @spacing-100;
	}
}
</style>
