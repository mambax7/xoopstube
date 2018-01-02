<?php

/**
 * Module: XoopsTube
 *
 * You may not change or alter any portion of this comment or credits
 * of supporting developers from this source code or any supporting source code
 * which is considered copyrighted (c) material of the original comment or credit authors.
 *
 * PHP version 5
 *
 * @category        Module
 * @package         Xoopstube
 * @author          XOOPS Development Team
 * @copyright       2001-2016 XOOPS Project (https://xoops.org)
 * @license         GNU GPL 2 or later (http://www.gnu.org/licenses/gpl-2.0.html)
 * @link            https://xoops.org/
 * @since           1.0.6
 */

use Xmf\Request;
use Xoopsmodules\xoopstube;

include __DIR__ . '/header.php';

// Begin Main page Heading etc
$cid        = Request::getInt('cid', 0, 'GET'); //xtubeCleanRequestVars($_REQUEST, 'cid', 0);
$selectdate = Request::getString('selectdate', ''); //xtubeCleanRequestVars($_REQUEST, 'selectdate', '');
$list       = Request::getString('list', '');// xtubeCleanRequestVars($_REQUEST, 'list', '');
$start      = Request::getInt('start', 0, 'GET'); //xtubeCleanRequestVars($_REQUEST, 'start', 0);

$catsort = $GLOBALS['xoopsModuleConfig']['sortcats'];
$mytree  = new xoopstube\Tree($GLOBALS['xoopsDB']->prefix('xoopstube_cat'), 'cid', 'pid');
$arr     = $mytree->getFirstChild($cid, $catsort);

if (is_array($arr) > 0 && !$list && !$selectdate) {
    if (false === xoopstube\Utility::xtubeCheckGroups($cid)) {
        redirect_header('index.php', 1, _MD_XOOPSTUBE_MUSTREGFIRST);
    }
}

$GLOBALS['xoopsOption']['template_main'] = 'xoopstube_viewcat.tpl';

include XOOPS_ROOT_PATH . '/header.php';
$xoTheme->addStylesheet('modules/' . $moduleDirName . '/assets/css/xtubestyle.css');
global $xoopsModule;
$catarray['letters'] = xoopstube\Utility::xtubeGetLetters();
//$catarray['letters']     = xoopstube\Utility::xoopstubeLettersChoice();
$catarray['imageheader'] = xoopstube\Utility::xtubeRenderImageHeader();
$xoopsTpl->assign('catarray', $catarray);

//$catArray['letters'] = xoopstube\Utility::xtubeLettersChoice();
//$catArray['letters'] = xoopstube\Utility::xoopstubeLettersChoice();
//$catArray['toolbar'] = xoopstube_toolbar();
//$xoopsTpl->assign('catarray', $catArray);

// Breadcrumb
$pathstring = '<li><a href="index.php">' . _MD_XOOPSTUBE_MAIN . '</a></li>';
$pathstring .= $mytree->getNicePathFromId($cid, 'title', 'viewcat.php?op=');
$xoopsTpl->assign('category_path', $pathstring);
$xoopsTpl->assign('category_id', $cid);

// Display Sub-categories for selected Category
if (is_array($arr) > 0 && !$list && !$selectdate) {
    $scount = 1;
    foreach ($arr as $ele) {
        if (false === xoopstube\Utility::xtubeCheckGroups($ele['cid'])) {
            continue;
        }
        $sub_arr         = [];
        $sub_arr         = $mytree->getFirstChild($ele['cid'], $catsort);
        $space           = 1;
        $chcount         = 1;
        $infercategories = '';
        foreach ($sub_arr as $sub_ele) {
            // Subitem file count
            $hassubitems = xoopstube\Utility::xtubeGetTotalItems($sub_ele['cid']);
            // Filter group permissions
            if (true === xoopstube\Utility::xtubeCheckGroups($sub_ele['cid'])) {
                // If subcategory count > 5 then finish adding subcats to $infercategories and end
                if ($chcount > 5) {
                    $infercategories .= '...';
                    break;
                }
                if ($space > 0) {
                    $infercategories .= ', ';
                }

                $infercategories .= '<a href="' . XOOPS_URL . '/modules/' . $xoopsModule->getVar('dirname') . '/viewcat.php?cid=' . $sub_ele['cid'] . '">' . $xtubemyts->htmlSpecialCharsStrip($sub_ele['title']) . '</a> (' . $hassubitems['count'] . ')';
                ++$space;
                ++$chcount;
            }
        }
        $totalvideos = xoopstube\Utility::xtubeGetTotalItems($ele['cid']);
        $indicator   = xoopstube\Utility::xtubeIsNewImage($totalvideos['published']);

        // This code is copyright WF-Projects
        // Using this code without our permission or removing this code voids the license agreement

        $_image = $ele['imgurl'] ? urldecode($ele['imgurl']) : '';
        if ('' !== $_image && $GLOBALS['xoopsModuleConfig']['usethumbs']) {
            $_thumb_image = new XtubeThumbsNails($_image, $GLOBALS['xoopsModuleConfig']['catimage'], 'thumbs');
            if ($_thumb_image) {
                $_thumb_image->setUseThumbs(1);
                $_thumb_image->setImageType('gd2');
                $_image = $_thumb_image->createThumbnail($GLOBALS['xoopsModuleConfig']['shotwidth'], $GLOBALS['xoopsModuleConfig']['shotheight'], $GLOBALS['xoopsModuleConfig']['imagequality'], $GLOBALS['xoopsModuleConfig']['updatethumbs'], $GLOBALS['xoopsModuleConfig']['imageAspect']);
            }
        }

        if (empty($_image) || '' == $_image) {
            $imgurl  = $indicator['image'];
            $_width  = 33;
            $_height = 24;
        } else {
            $imgurl  = "{$GLOBALS['xoopsModuleConfig']['catimage']}/$_image";
            $_width  = $GLOBALS['xoopsModuleConfig']['shotwidth'];
            $_height = $GLOBALS['xoopsModuleConfig']['shotheight'];
        }
        /*
        * End
        */

        $xoopsTpl->append('subcategories', [
            'title'           => $xtubemyts->htmlSpecialCharsStrip($ele['title']),
            'id'              => $ele['cid'],
            'image'           => XOOPS_URL . "/$imgurl",
            'width'           => $_width,
            'height'          => $_height,
            'infercategories' => $infercategories,
            'totalvideos'     => $totalvideos['count'],
            'count'           => $scount,
            'alttext'         => $ele['description']
        ]);
        ++$scount;
    }
}

// Show Description for Category listing
$sql         = 'SELECT * FROM ' . $GLOBALS['xoopsDB']->prefix('xoopstube_cat') . ' WHERE cid=' . $cid;
$head_arr    = $GLOBALS['xoopsDB']->fetchArray($GLOBALS['xoopsDB']->query($sql));
$html        = $head_arr['nohtml'] ? 0 : 1;
$smiley      = $head_arr['nosmiley'] ? 0 : 1;
$xcodes      = $head_arr['noxcodes'] ? 0 : 1;
$images      = $head_arr['noimages'] ? 0 : 1;
$breaks      = $head_arr['nobreak'] ? 1 : 0;
$description =& $xtubemyts->displayTarea($head_arr['description'], $html, $smiley, $xcodes, $images, $breaks);
$xoopsTpl->assign('description', $description);
/** @var XoopsModuleHandler $moduleHandler */
$moduleHandler = xoops_getHandler('module');
$versioninfo   = $moduleHandler->get($xoopsModule->getVar('mid'));
if ($head_arr['title'] > '') {
    $xoopsTpl->assign('xoops_pagetitle', $versioninfo->getInfo('name') . ':&nbsp;' . $head_arr['title']);
} else {
    $xoopsTpl->assign('xoops_pagetitle', $versioninfo->getInfo('name'));
}

if ($head_arr['client_id'] > 0) {
    $catarray['imageheader'] = xoopstube\Utility::xtubeGetBannerFromClientId($head_arr['client_id']);
} elseif ($head_arr['banner_id'] > 0) {
    $catarray['imageheader'] = xoopstube\Utility::xtubeGetBannerFromBannerId($head_arr['banner_id']);
} else {
    $catarray['imageheader'] = xoopstube\Utility::xtubeRenderImageHeader();
}
$xoopsTpl->assign('catarray', $catarray);
// Extract linkload information from database
$xoopsTpl->assign('show_categort_title', true);

$orderby0 = (isset($_REQUEST['orderby'])
             && !empty($_REQUEST['orderby'])) ? xoopstube\Utility::xtubeConvertOrderByIn(htmlspecialchars($_REQUEST['orderby'])) : xoopstube\Utility::xtubeConvertOrderByIn($GLOBALS['xoopsModuleConfig']['linkxorder']);
$orderby  = Request::getString('orderby', '', 'GET') ? xoopstube\Utility::xtubeConvertOrderByIn(Request::getString('orderby', '', 'GET')) : xoopstube\Utility::xtubeConvertOrderByIn($GLOBALS['xoopsModuleConfig']['linkxorder']);

if ($selectdate) {
    $d = date('j', $selectdate);
    $m = date('m', $selectdate);
    $y = date('Y', $selectdate);

    $stat_begin = mktime(0, 0, 0, $m, $d, $y);
    $stat_end   = mktime(23, 59, 59, $m, $d, $y);

    $query  = ' WHERE published>=' . $stat_begin . ' AND published<=' . $stat_end . ' AND (expired=0 OR expired>' . time() . ') AND offline=0 AND cid>0';
    $sql    = 'SELECT * FROM ' . $GLOBALS['xoopsDB']->prefix('xoopstube_videos') . $query . ' ORDER BY ' . $orderby;
    $result = $GLOBALS['xoopsDB']->query($sql, $GLOBALS['xoopsModuleConfig']['perpage'], $start);

    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['xoopsDB']->prefix('xoopstube_videos') . $query;
    list($count) = $GLOBALS['xoopsDB']->fetchRow($GLOBALS['xoopsDB']->query($sql));

    $list_by = 'selectdate=' . $selectdate;

    $xoopsTpl->assign('is_selectdate', true);
    $xoopsTpl->assign('selected_date', xoopstube\Utility::xtubeGetTimestamp(formatTimestamp($selectdate, $GLOBALS['xoopsModuleConfig']['dateformat'])));
} elseif ($list) {
    $query = " WHERE title LIKE '$list%' AND (published>0 AND published<=" . time() . ') AND (expired=0 OR expired>' . time() . ') AND offline=0 AND cid>0';

    $sql    = 'SELECT * FROM ' . $GLOBALS['xoopsDB']->prefix('xoopstube_videos') . $query . ' ORDER BY ' . $orderby;
    $result = $GLOBALS['xoopsDB']->query($sql, $GLOBALS['xoopsModuleConfig']['perpage'], $start);

    $sql = 'SELECT COUNT(*) FROM ' . $GLOBALS['xoopsDB']->prefix('xoopstube_videos') . $query;
    list($count) = $GLOBALS['xoopsDB']->fetchRow($GLOBALS['xoopsDB']->query($sql));
    $list_by = "list=$list";
} else {
    $query = 'WHERE a.published>0 AND a.published<=' . time() . ' AND (a.expired=0 OR a.expired>' . time() . ') AND a.offline=0' . ' AND (b.cid=a.cid OR (a.cid=' . $cid . ' OR b.cid=' . $cid . '))';

    $sql    = 'SELECT DISTINCT a.* FROM ' . $GLOBALS['xoopsDB']->prefix('xoopstube_videos') . ' a LEFT JOIN ' . $GLOBALS['xoopsDB']->prefix('xoopstube_altcat') . ' b ON b.lid=a.lid ' . $query . ' ORDER BY ' . $orderby;
    $result = $GLOBALS['xoopsDB']->query($sql, $GLOBALS['xoopsModuleConfig']['perpage'], $start);

    $sql2 = 'SELECT COUNT(*) FROM ' . $GLOBALS['xoopsDB']->prefix('xoopstube_videos') . ' a LEFT JOIN ' . $GLOBALS['xoopsDB']->prefix('xoopstube_altcat') . ' b ON b.lid=a.lid ' . $query;
    list($count) = $GLOBALS['xoopsDB']->fetchRow($GLOBALS['xoopsDB']->query($sql2));
    $order   = xoopstube\Utility::xtubeConvertOrderByOut($orderby);
    $list_by = 'cid=' . $cid . '&orderby=' . $order;
    $xoopsTpl->assign('show_categort_title', false);
}
$pagenav = new \XoopsPageNav($count, $GLOBALS['xoopsModuleConfig']['perpage'], $start, 'start', $list_by);

// Show videos
if ($count > 0) {
    $moderate = 0;
    while (false !== ($video_arr = $GLOBALS['xoopsDB']->fetchArray($result))) {
        if (true === xoopstube\Utility::xtubeCheckGroups($video_arr['cid'])) {
            require XOOPS_ROOT_PATH . '/modules/' . $xoopsModule->getVar('dirname') . '/include/videoloadinfo.php';
            $xoopsTpl->append('video', $video);
        }
    }

    unset($video_arr);

    // Show order box
    $xoopsTpl->assign('show_videos', false);
    if ($count > 1 && 0 !== $cid) {
        $xoopsTpl->assign('show_videos', true);
        $orderbyTrans = xoopstube\Utility::xtubeConvertOrderByTrans($orderby);
        $xoopsTpl->assign('lang_cursortedby', sprintf(_MD_XOOPSTUBE_CURSORTBY, xoopstube\Utility::xtubeConvertOrderByTrans($orderby)));
        $orderby = xoopstube\Utility::xtubeConvertOrderByOut($orderby);
    }

    // Screenshots display
    $xoopsTpl->assign('show_screenshot', false);
    if (isset($GLOBALS['xoopsModuleConfig']['screenshot']) && 1 == $GLOBALS['xoopsModuleConfig']['screenshot']) {
        $xoopsTpl->assign('shotwidth', $GLOBALS['xoopsModuleConfig']['shotwidth']);
        $xoopsTpl->assign('shotheight', $GLOBALS['xoopsModuleConfig']['shotheight']);
        $xoopsTpl->assign('show_screenshot', true);
    }

    // Nav page render
    $page_nav = $pagenav->renderNav();
    $istrue   = (isset($page_nav) && !empty($page_nav)) ? true : false;
    $xoopsTpl->assign('page_nav', $istrue);
    $xoopsTpl->assign('pagenav', $page_nav);
    $xoopsTpl->assign('module_dir', $xoopsModule->getVar('dirname'));
}

$xoopsTpl->assign('cat_columns', $GLOBALS['xoopsModuleConfig']['catcolumns']);

include XOOPS_ROOT_PATH . '/footer.php';
