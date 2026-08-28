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
{*
 * Renders a description field's template by a variable file name.
 *
 * This helper exists solely so the dynamic {include file=$desc_template} is
 * NOT compiled inside a {block} tag. Older Smarty versions bundled with
 * PrestaShop 1.7.0-1.7.5 throw "variable template file names not allow within
 * {block} tags" when a variable {include} appears inside a {block}. A static
 * {include} of this helper is allowed inside a block, and this helper itself
 * contains no {block}, so the variable include below compiles everywhere.
 *
 * Relative paths (e.g. './auth-info.tpl') resolve against this template's
 * directory, which is the same form/ directory as the caller, so resolution
 * is unchanged.
 *}
{if isset($desc_template) && $desc_template}
    {include file=$desc_template}
{/if}
