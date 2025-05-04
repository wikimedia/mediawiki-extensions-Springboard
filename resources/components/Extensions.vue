<template>
    <cdx-table
		caption="Your custom list of extensions"
		:columns="columns"
		:data="data"
        :paginate="true"
	>
        <template #item-name="{ item }">
			<a :href="`https://www.mediawiki.org/wiki/Extension:${ item }`">{{ item }}</a>
		</template>
        <template #item-action="{ item }">
			<cdx-button v-if="item.exists" action="destructive" weight="primary" @click="submit(item)">{{ item.action }}</cdx-button>
			<cdx-button v-else action="progressive" weight="primary" @click="submit(item)">{{ item.action }}</cdx-button>
		</template>
	</cdx-table>
</template>

<script>
const { ref } = require( 'vue' );
const { CdxTable, CdxButton } = require( '../codex.js' );

// @vue/component
module.exports = {
	name: 'Extensions',
	components: {
        CdxTable,
        CdxButton
    },
	setup() {
        let data = mw.config.get( 'WTExtensions' );
        let version = mw.config.get( 'wgVersion' ).split( '.' );
		const mwVersion = `REL${version[0]}_${version[1]}`;
        data = data.map( (key) => {
            const extName = Object.keys(key)[0];
            updatedMap = { ...Object.values(key)[0], name: extName };
            if ( !( 'branch' in updatedMap ) ) {
                updatedMap[ 'branch' ] = mwVersion;
            }
            if ( !( 'commit' in updatedMap ) ) {
                updatedMap[ 'commit' ] = "LATEST";
            }
            let mapCopy = {...updatedMap};
            let installActionName = "Install";
            let uninstallActionName = "Uninstall";
            if ( 'bundled' in updatedMap ) { 
                installActionName = 'Enable';
                uninstallActionName = 'Disable';
            }
            updatedMap['action'] = updatedMap['exists']
                ? { ...mapCopy, action: uninstallActionName }
                : { ...mapCopy, action: installActionName }
            return updatedMap;
        } );
		return {
            'data': data,
            'columns': [
                {id: 'name', label: 'Extension Name', sortable: true},
                {id: 'commit', label: 'Commit', sortable: true},
                {id: 'branch', label: 'Branch', sortable: true},
                {id: 'action', label: 'Action', sortable: true}
            ]
        };
	},
    methods: {
        submit ( data ) {
            var api = new mw.Api();
            var action = data['action'].toLowerCase();
            if ( action == 'enable' ) {
                action = 'install';
            } else if ( action == 'disable' ) {
                action = 'uninstall';  
            }
            var payload = {
                action: 'springboard',
                wtaction: action,
                wtname: data['name'],
                wtrepo: data.hasOwnProperty('repository') ? data['repository'] : false,
                wtcommit: data['commit'],
                wtdbupdate: (data.hasOwnProperty('additional steps') && data['additional steps'].includes('database update')) ?? false,
                wtcomposer: (data.hasOwnProperty('additional steps') && data['additional steps'].includes('composer update')) ?? false,
                wtbranch: data['branch'],
                wttype: 'extension',
                wtbundled: data.hasOwnProperty('repository') ? data['bundled'] : false
            };
            api.postWithToken( 'csrf', payload ).then( function (res) {
                mw.notify( res.springboard.result );
              // Response handling
            } ).fail( function ( code, msg ) {
                mw.notify( api.getErrorMessage( msg ), { type: 'error' } );
            } );
        }
    },
};
</script>

<style lang="less">
@import 'mediawiki.skin.variables.less';
</style>
