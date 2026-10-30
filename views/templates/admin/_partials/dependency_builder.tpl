{*
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
 * @copyright      Copyright (c) 2015-2026
 * @license        https://www.gnu.org/licenses/lgpl-3.0.en.html GNU Lesser General Public License
*}
<div id="cdc-container"></div>
{literal}
<script>
  if (window.mboCdcDependencyResolver === undefined) {
    const mboCdn = document.createElement('script');
    mboCdn.src = 'https://assets.prestashop3.com/dst/mbo/v1/mbo-cdc-dependencies-resolver.umd.js';
    document.body.appendChild(mboCdn);
  }

  setTimeout(function(){
    const init = function() {
      if (!window.mboCdcDependencyResolver) {
        setTimeout(init, 200);
        return;
      }

      const renderMboCdcDependencyResolver = window.mboCdcDependencyResolver.render
      const context = {
        ...{/literal}{$dependencies|json_encode}{literal},
        onDependenciesResolved: () => location.reload(),
        onDependencyFailed: (dependencyData) => console.log('Failed to install dependency', dependencyData),
      }
      renderMboCdcDependencyResolver(context, '#cdc-container');
    }

    init();
  }, 0);

</script>
{/literal}
