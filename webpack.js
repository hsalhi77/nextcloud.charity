const webpackConfig = require('@nextcloud/webpack-vue-config')
const { merge } = require('webpack-merge')

module.exports = merge(webpackConfig, {
	output: {
		filename: 'charity.js',
		chunkFilename: 'charity-[name].js?v=[contenthash]',
		clean: {
			keep: /helper\.js|function\.js|.*\.(jpg|jpeg|png|svg|gif)$/,
		},
	},
})
