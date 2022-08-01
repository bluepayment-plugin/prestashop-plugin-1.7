/**
 * BlueMedia_BluePayment extension
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the GNU Lesser General Public License
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * https://www.gnu.org/licenses/lgpl-3.0.en.html
 *
 * @category       BlueMedia
 * @package        BlueMedia_BluePayment
 * @copyright      Copyright (c) 2015-2022
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
 */

const path = require('path');
const webpack = require('webpack');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const UglifyJsPlugin = require('uglifyjs-webpack-plugin');
const METADATA = "/**\n" +
	" * BlueMedia_BluePayment extension\n" +
	" *\n" +
	" * NOTICE OF LICENSE\n" +
	" *\n" +
	" * This source file is subject to the GNU Lesser General Public License\n" +
	" * that is bundled with this package in the file LICENSE.txt.\n" +
	" * It is also available through the world-wide-web at this URL:\n" +
	" * https://www.gnu.org/licenses/lgpl-3.0.en.html\n" +
	" *\n" +
	" * @category       BlueMedia\n" +
	" * @package        BlueMedia_BluePayment\n" +
	" * @copyright      Copyright (c) 2015-2022\n" +
	" * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License\n" +
	" */";

let config = {
	target: ["web", "es5"],
	entry: {
		front: ['./views/js/front.js', './views/scss/front.scss'],
		// error: ['./css/error.scss'],
	},
	output: {
		path: path.resolve(__dirname, './views/js'),
		filename: '[name].min.js',
	},
	resolve: {
		preferRelative: true,
		extensions: ['*', '.js']
	},
	module: {
		rules: [
			{
				test: /\.m?js$/,
				exclude: /node_modules/,
				use: {
					loader: 'babel-loader',
					options: {
						presets: [
							['@babel/preset-env', { targets: "ie 11" }]
						]
					}
				}
			},
			{
				test: /\.js/,
				loader: 'esbuild-loader',
			},
			{
				test: /\.scss$/,
				use: [
					MiniCssExtractPlugin.loader,
					'css-loader',
					'postcss-loader',
					'sass-loader',
				],
			},
			{
			  test: /.(png|woff(2)?|eot|otf|ttf|svg|gif)(\?[a-z0-9=\.]+)?$/,
			  use: [
			    {
			      loader: 'file-loader',
			      options: {
			        name: '../css/[hash].[ext]',
			      },
			    },
			  ],
			},
			{
				test: /\.css$/,
				use: [MiniCssExtractPlugin.loader, 'style-loader', 'css-loader', 'postcss-loader'],
			},
		],
	},
	plugins: [
		new MiniCssExtractPlugin({filename: path.join('..', 'css', '[name].css')}),
		new webpack.BannerPlugin({
			banner: METADATA,
			raw: true,
			entryOnly: true,
		}),
	]
};

if (process.env.NODE_ENV === 'production') {
	config.optimization = {
		minimizer: [
			new UglifyJsPlugin({
				sourceMap: false,
				extractComments: false,
				uglifyOptions: {
					compress: {
						sequences: true,
						conditionals: true,
						booleans: true,
						if_return: true,
						join_vars: true,
						drop_console: true,
					},
					output: {
						beautify: false,
						comments: false,
						// comments: 'some',
						preamble: METADATA,
					},
					mangle: { // see https://github.com/mishoo/UglifyJS2#mangle-options
						keep_fnames: false,
						toplevel: true,
					},
				}
			})
		]
	}
} else {
	config.optimization = {
		minimizer: [
			new UglifyJsPlugin({
				sourceMap: true,
				extractComments: false,
				uglifyOptions: {
					// compress: {
					// 	sequences: true,
					// 	conditionals: true,
					// 	booleans: true,
					// 	if_return: true,
					// 	join_vars: true,
					// 	drop_console: true,
					// },
					output: {
						comments: false,
					},
				}
			})
		]
	}
}

// config.mode = 'development';
config.mode = 'production';

module.exports = config;
