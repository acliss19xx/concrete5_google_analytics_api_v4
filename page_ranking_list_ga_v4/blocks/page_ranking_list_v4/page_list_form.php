<?php defined('C5_EXECUTE') or die("Access Denied.");
$c = Page::getCurrentPage();
$form = Loader::helper('form/page_selector');
?>

<?php echo Loader::helper('concrete/ui')->tabs(array(
    array('page-list-settings', t('Settings'), true),
    array('page-list-preview', t('Preview'))
));?>

<div class="ccm-tab-content" id="ccm-tab-content-page-list-settings">
    <div class=" pagelist-form">

        <input type="hidden" name="pageListToolsDir" value="<?php echo Loader::helper('concrete/urls')->getBlockTypeToolsURL($bt) ?>/"/>
        
        <?php
        $al = Core::make('helper/concrete/asset_library');
        ?>
        
        <fieldset>
            <legend><?php echo t('Analytics Service Json File') ?></legend>
        
            <div class="form-group">
                <label class="control-label"><?php echo t('Analytics service json file')?></label>
                <?php
                echo $al->file('ccm-b-file', 'analyticsServiceJson', t('Choose file'),$bf);
                ?>
            </div>
            <div class="form-group">
                <label class='control-label'><?php echo t('Analytics View ID') ?></label>
                <input type="text" name="analyticsViewID" value="<?php echo $analyticsViewID ?>" class="form-control">
            </div>
        
        </fieldset>

        <fieldset>
            <div class="form-group">
                <div class="row">
                    <div class="col-xs-6">
                        <label for="analyticsStartDate" class="control-label"><?php echo t('pv start date')?></label>
                        <select name="analyticsStartDate" id="analyticsStartDate" class="form-control" onChange="analyticsDateSwap()">
                            <?php
                            $daysAgo = array("31" => "1 month ago",
                                             "0" => "today",
                                             "1"=> "1 days ago",
                                             "7" => "1 week ago",
                                             "14" => "2 week ago",
                                             "62" => "2 month ago",
                                             "365" => "1 year ago");
                            foreach($daysAgo as $key => $value){ ?>
                                <option value="<?php echo $key?>" <?php echo $analyticsStartDate == $key ? 'selected':'' ?>>
                                    <?php echo t($value) ?>
                                </option>
                            <?php } ?>
                        </select>
                    </div>
                    <div class="col-xs-6">
                        <label for="analyticsEndDate" class="control-label"><?php echo t('pv end date')?></label>
                        <select name="analyticsEndDate" id="analyticsEndDate" class="form-control" onChange="analyticsDateSwap()">
                            <?php
                            $daysAgo = array("0" => "today",
                                             "1"=> "1 days ago",
                                             "7" => "1 week ago",
                                             "14" => "2 week ago",
                                             "31" => "1 month ago",
                                             "62" => "2 month ago",
                                             "365" => "1 year ago");
                            foreach($daysAgo as $key => $value){ ?>
                                <option value="<?php echo $key?>" <?php echo $analyticsEndDate == $key ? 'selected':'' ?>>
                                    <?php echo t($value) ?>
                            </option>
                            <?php } ?>
                        </select>
                    </div>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <div class="form-group">
                <label class="control-label"><?php echo t('Sort')?></label>
                <select name="orderBy" class="form-control">
                    <option value="ranking_desc" <?php if ($orderBy == 'ranking_desc') {
                        ?> selected <?php
                    } ?>>
                        <?php echo t('descending order of PV') ?>
                    </option>
                    <option value="ranking_asc" <?php if ($orderBy == 'ranking_asc') {
                        ?> selected <?php
                    } ?>>
                        <?php echo t('order of PV') ?>
                    </option>
                </select>
            </div>
        </fieldset>

        <fieldset>
            <div class="form-group">
                <label class='control-label'><?php echo t('Number of Pages to Display') ?></label>
                <input type="text" name="num" value="<?php echo $num ?>" class="form-control">
            </div>

            <div class="form-group">
                <label class="control-label"><?php echo t('Page Type') ?></label>
                <?php
                $ctArray = PageType::getList();

                if (is_array($ctArray)) {
                    ?>
                    <select class="form-control" name="ptID" id="selectPTID">
                        <option value="0">** <?php echo t('All') ?> **</option>
                        <?php
                        foreach ($ctArray as $ct) {
                            ?>
                            <option
                                value="<?php echo $ct->getPageTypeID() ?>" <?php if ($ptID == $ct->getPageTypeID()) {
                                ?> selected <?php
                            }
                            ?>>
                                <?php echo $ct->getPageTypeDisplayName() ?>
                            </option>
                            <?php

                        }
                        ?>
                    </select>
                    <?php

                }
                ?>
            </div>
        </fieldset>

        <fieldset>
            <div class="form-group">
                <label class="control-label"><?php echo t('Topics') ?></label>
                <div class="radio">
                    <label>
                        <input type="radio" name="topicFilter" id="topicFilter"
                               value="" <?php if (!$filterByRelated && !$filterByCustomTopic) {
                            ?> checked<?php
                        } ?> />
                        <?php echo t('No topic filtering') ?>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="topicFilter" id="topicFilterCustom"
                               value="custom" <?php if ($filterByCustomTopic) {
                            ?> checked<?php
                        } ?>>
                        <?php echo t('Custom Topic') ?>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="topicFilter" id="topicFilterRelated"
                               value="related" <?php if ($filterByRelated) {
                            ?> checked<?php
                        } ?> >
                        <?php echo t('Related Topic') ?>
                    </label>
                </div>
                <div data-row="custom-topic">
                    <select class="form-control" name="customTopicAttributeKeyHandle" id="customTopicAttributeKeyHandle">
                        <option value=""><?php echo t('Choose topics attribute.')?></option>
                        <?php foreach ($attributeKeys as $attributeKey) {
                            $attributeController = $attributeKey->getController();
                            ?>
                            <option data-topic-tree-id="<?php echo $attributeController->getTopicTreeID()?>" value="<?php echo $attributeKey->getAttributeKeyHandle()?>" <?php if ($attributeKey->getAttributeKeyHandle() == $customTopicAttributeKeyHandle) {
                            ?>selected<?php
                            }
                            ?>><?php echo $attributeKey->getAttributeKeyDisplayName()?></option>
                            <?php
                        } ?>
                    </select>
                    <div class="tree-view-container">
                        <div class="tree-view-template">
                        </div>
                    </div>
                    <input type="hidden" name="customTopicTreeNodeID" value="<?php echo $customTopicTreeNodeID ?>">

                </div>
                <div data-row="related-topic">
                    <span class="help-block"><?php echo t('Allows other blocks like the topic list block to pass search criteria to this page list block.')?></span>
                    <select class="form-control" name="relatedTopicAttributeKeyHandle" id="relatedTopicAttributeKeyHandle">
                        <option value=""><?php echo t('Choose topics attribute.')?></option>
                        <?php foreach ($attributeKeys as $attributeKey) {
                            ?>
                            <option value="<?php echo $attributeKey->getAttributeKeyHandle()?>" <?php if ($attributeKey->getAttributeKeyHandle() == $relatedTopicAttributeKeyHandle) {
                            ?>selected<?php
                            }
                            ?>><?php echo $attributeKey->getAttributeKeyDisplayName()?></option>
                            <?php
                        } ?>
                    </select>
                </div>
            </div>

        </fieldset>

        <fieldset>
            <div class="form-group">
                <label class="control-label"><?php echo t('Filter by Public Date') ?></label>
                <?php
                $filterDateOptions = array(
                    'all' => t('Show All'),
                    'now' => t('Today'),
                    'past' => t('Before Today'),
                    'future' => t('After Today'),
                    'between' => t('Between'),
                );

                foreach ($filterDateOptions as $filterDateOptionHandle => $filterDateOptionLabel) {
                    $isChecked = ($filterDateOption == $filterDateOptionHandle) ? 'checked' : '';
                    ?>
                    <div class="radio">
                        <label>
                            <input type="radio" class='filterDateOption' name="filterDateOption" value="<?php echo $filterDateOptionHandle?>" <?php echo $isChecked?> />
                            <?php echo $filterDateOptionLabel ?>
                        </label>
                    </div>
                    <?php
                } ?>

                <div class="filterDateOptionDetail" data-filterDateOption="past">
                    <div class="form-group">
                        <label class="control-label"><?php echo t('Days in the Past')?> <i class="launch-tooltip fa fa-question-circle" title="<?php echo t('Leave 0 to show all past dated pages')?>"></i></label>
                        <input type="text" name="filterDatePast" value="<?php echo $filterDateDays ?>" class="form-control">
                    </div>
                </div>

                <div class="filterDateOptionDetail" data-filterDateOption="future">
                    <div class="form-group">
                        <label class="control-label"><?php echo t('Days in the Future')?> <i class="launch-tooltip fa fa-question-circle" title="<?php echo t('Leave 0 to show all future dated pages')?>"></i></label>
                        <input type="text" name="filterDateFuture" value="<?php echo $filterDateDays ?>" class="form-control">
                    </div>
                </div>

                <div class="filterDateOptionDetail" data-filterDateOption="between">
                    <?php
                    $datetime = loader::helper('form/date_time');
                    echo $datetime->date('filterDateStart', $filterDateStart);
                    echo "<p>and</p>";
                    echo $datetime->date('filterDateEnd', $filterDateEnd);
                    ?>
                </div>

            </div>

        </fieldset>

        <fieldset>
            <div class="form-group">
                <label class="control-label"><?php echo t('Other Filters') ?></label>
                <div class="checkbox">
                    <label>
                        <input <?php if (!is_object($featuredAttribute)) {
                            ?> disabled <?php
                        } ?> type="checkbox" name="displayFeaturedOnly"
                             value="1" <?php if ($displayFeaturedOnly == 1) {
                            ?> checked <?php
                        } ?>
                             style="vertical-align: middle"/>
                        <?php echo t('Featured pages only.') ?>
                    </label>
                    <?php if (!is_object($featuredAttribute)) {
                        ?>
                        <span class="help-block"><?php echo
                            t(
                                '(<strong>Note</strong>: You must create the "is_featured" page attribute first.)');
                            ?></span>
                        <?php
                    } ?>
                </div>

                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="displayAliases"
                               value="1" <?php if ($displayAliases == 1) {
                            ?> checked <?php
                        } ?> />
                        <?php echo t('Display page aliases.') ?>
                    </label>
                </div>

        <div class="checkbox">
            <label>
                <input type="checkbox" name="ignorePermissions"
                       value="1" <?php if ($ignorePermissions == 1) { ?> checked <?php } ?> />
                <?php echo t('Ignore page permissions.') ?>
            </label>
        </div>

            <div class="checkbox">
            <label>
                <input type="checkbox" name="enableExternalFiltering" value="1" <?php if ($enableExternalFiltering) { ?>checked<?php } ?> />
                <?php echo t('Enable Other Blocks to Filter This Page List.') ?>
            </label>
        </div>

        </fieldset>

        <fieldset>
            <div class="form-group">
                <label class="control-label"><?php echo t('Pagination')?></label>
                <div class="checkbox">
                    <label>
                        <input type="checkbox" name="paginate" value="1" <?php if ($paginate == 1) {
                            ?> checked <?php
                        } ?> />
                        <?php echo t('Display pagination interface if more items are available than are displayed.') ?>
                    </label>
                </div>
            </div>
        </fieldset>

        <fieldset>
            <div class="form-group">
                <label class="control-label"><?php echo t('Location')?></label>

                <div class="radio">
                    <label>
                        <input type="radio" name="cParentID" id="cEverywhereField"
                               value="0" <?php if ($cParentID == 0) {
                            ?> checked<?php
                        } ?> />
                        <?php echo t('Everywhere') ?>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="cParentID" id="cThisPageField"
                               value="<?php echo $c->getCollectionID() ?>" <?php if ($cThis) {
                            ?> checked<?php
                        } ?>>
                        <?php echo t('Beneath this page') ?>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="cParentID" id="cThisParentField"
                               value="<?php echo $c->getCollectionParentID() ?>" <?php if ($cThisParent) { ?> checked<?php } ?>>
                        <?php echo t('At the current level') ?>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="cParentID" id="cOtherField"
                               value="OTHER" <?php if ($isOtherPage) {
                            ?> checked<?php
                        } ?>>
                        <?php echo t('Beneath another page') ?>
                    </label>
                </div>

                <div class="ccm-page-list-page-other" <?php if (!$isOtherPage) {
                    ?> style="display: none" <?php
                } ?>>

                    <?php echo $form->selectPage('cParentIDValue', $isOtherPage ? $cParentID : false); ?>
                </div>

                <div class="ccm-page-list-all-descendents"
                     style="<?php echo ($cParentID === 0) ? ' display: none;' : ''; ?>">
                    <div class="checkbox">
                        <label>
                            <input type="checkbox" name="includeAllDescendents" id="includeAllDescendents"
                                   value="1" <?php echo $includeAllDescendents ? 'checked="checked"' : '' ?> />
                            <?php echo t('Include all child pages') ?>
                        </label>
                    </div>
                </div>

            </div>

        </fieldset>

        <fieldset>
            <legend><?php echo t('Output') ?></legend>
            <div class="form-group">
                <label class="control-label"><?php echo t('Provide RSS Feed') ?></label>
                <div class="radio">
                    <label>
                        <input type="radio" name="rss" class="rssSelector"
                               value="0" <?php echo (is_object($rssFeed) ? "" : "checked=\"checked\"") ?>/> <?php echo t('No') ?>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input id="ccm-pagelist-rssSelectorOn" type="radio" name="rss" class="rssSelector"
                               value="1" <?php echo (is_object($rssFeed) ? "checked=\"checked\"" : "") ?>/> <?php echo t('Yes') ?>
                    </label>
                </div>
                <div id="ccm-pagelist-rssDetails" <?php echo (is_object($rssFeed) ? "" : "style=\"display:none;\"") ?>>
                    <?php if (is_object($rssFeed)) {
                        ?>
                        <?php echo t('RSS Feed can be found here: <a href="%s" target="_blank">%s</a>', $rssFeed->getFeedURL(), $rssFeed->getFeedURL())?>
                        <?php
                    } else {
                        ?>
                        <div class="form-group">
                            <label class="control-label"><?php echo t('RSS Feed Title') ?></label>
                            <input class="form-control" id="ccm-pagelist-rssTitle" type="text" name="rssTitle"
                                   value=""/>
                        </div>
                        <div class="form-group">
                            <label class="control-label"><?php echo t('RSS Feed Description') ?></label>
                            <textarea name="rssDescription" class="form-control"></textarea>
                        </div>
                        <div class="form-group">
                            <label class="control-label"><?php echo t('RSS Feed Location') ?></label>
                            <div class="input-group">
                                <span class="input-group-addon"><?php echo URL::to('/rss')?>/</span>
                                <input type="text" name="rssHandle" value="" />
                            </div>
                        </div>
                        <?php
                    } ?>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label"><?php echo t('Include Page Name') ?></label>
                <div class="radio">
                    <label>
                        <input type="radio" name="includeName"
                               value="0" <?php echo ($includeName ? "" : "checked=\"checked\"") ?>/> <?php echo t('No') ?>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="includeName"
                               value="1" <?php echo ($includeName ? "checked=\"checked\"" : "") ?>/> <?php echo t('Yes') ?>
                    </label>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label"><?php echo t('Include Page Description') ?></label>
                <div class="radio">
                    <label>
                        <input type="radio" name="includeDescription"
                               value="0" <?php echo ($includeDescription ? "" : "checked=\"checked\"") ?>/> <?php echo t('No') ?>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="includeDescription"
                               value="1" <?php echo ($includeDescription ? "checked=\"checked\"" : "") ?>/> <?php echo t('Yes') ?>
                    </label>
                </div>
                <div class="ccm-page-list-truncate-description" <?php echo ($includeDescription ? "" : "style=\"display:none;\"") ?>>
                    <label class="control-label"><?php echo t('Display Truncated Description')?></label>
                    <div class="input-group">
            <span class="input-group-addon">
                <input id="ccm-pagelist-truncateSummariesOn" name="truncateSummaries" type="checkbox"
                       value="1" <?php echo ($truncateSummaries ? "checked=\"checked\"" : "") ?> />
            </span>
                        <input class="form-control" id="ccm-pagelist-truncateChars" <?php echo ($truncateSummaries ? "" : "disabled=\"disabled\"") ?>
                               type="text" name="truncateChars" size="3" value="<?php echo intval($truncateChars) ?>" />
            <span class="input-group-addon">
                <?php echo t('characters') ?>
            </span>
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label"><?php echo t('Include Public Page Date') ?></label>
                <div class="radio">
                    <label>
                        <input type="radio" name="includeDate"
                               value="0" <?php echo ($includeDate ? "" : "checked=\"checked\"") ?>/> <?php echo t('No') ?>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="includeDate"
                               value="1" <?php echo ($includeDate ? "checked=\"checked\"" : "") ?>/> <?php echo t('Yes') ?>
                    </label>
                </div>
                <span class="help-block"><?php echo t('This is usually the date the page is created. It can be changed from the page attributes panel.')?></span>
            </div>
            <div class="form-group">
                <label class="control-label"><?php echo t('Display Thumbnail Image') ?></label>
                <div class="radio">
                    <label>
                        <input type="radio" name="displayThumbnail"
                            <?php echo (!is_object($thumbnailAttribute) ? 'disabled ' : '')?>
                               value="0" <?php echo ($displayThumbnail ? "" : "checked=\"checked\"") ?>/> <?php echo t('No') ?>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="displayThumbnail"
                            <?php echo (!is_object($thumbnailAttribute) ? 'disabled ' : '')?>
                               value="1" <?php echo ($displayThumbnail ? "checked=\"checked\"" : "") ?>/> <?php echo t('Yes') ?>
                    </label>
                </div>
                <?php if (!is_object($thumbnailAttribute)) {
                    ?>
                    <div class="help-block">
                        <?php echo t('You must create an attribute with the \'thumbnail\' handle in order to use this option.')?>
                    </div>
                    <?php
                } ?>
            </div>

            <div class="form-group">
                <label class="control-label"><?php echo t('Use Different Link than Page Name') ?></label>
                <div class="radio">
                    <label>
                        <input type="radio" name="useButtonForLink"
                               value="0" <?php echo ($useButtonForLink ? "" : "checked=\"checked\"") ?>/> <?php echo t('No') ?>
                    </label>
                </div>
                <div class="radio">
                    <label>
                        <input type="radio" name="useButtonForLink"
                               value="1" <?php echo ($useButtonForLink ? "checked=\"checked\"" : "") ?>/> <?php echo t('Yes') ?>
                    </label>
                </div>
                <div class="ccm-page-list-button-text" <?php echo ($useButtonForLink ? "" : "style=\"display:none;\"") ?>>
                    <div class="form-group">
                        <label class="control-label"><?php echo t('Link Text') ?></label>
                        <input class="form-control" type="text" name="buttonLinkText" value="<?php echo $buttonLinkText?>" />
                    </div>
                </div>
            </div>

            <div class="form-group">
                <label class="control-label"><?php echo t('Title of Page List') ?></label>
                <input type="text" class="form-control" name="pageListTitle" value="<?php echo $pageListTitle?>" />
            </div>

            <div class="form-group">
                <label class="control-label"><?php echo t('Message to Display When No Pages Listed.') ?></label>
                <textarea class="form-control" name="noResultsMessage"><?php echo $noResultsMessage?></textarea>
            </div>
            <fieldset>


                <div class="loader">
                    <i class="fa fa-cog fa-spin"></i>
                </div>

    </div>
</div>

<div class="ccm-tab-content" id="ccm-tab-content-page-list-preview">
    <div class="preview">
        <div class="render">

        </div>
        <div class="cover"></div>
    </div>
</div>

<style type="text/css">

    div.pagelist-form div.cover {
        width: 100%;
        height: 100%;
        position: absolute;
        top: 0;
        left: 0;
    }

    div.pagelist-form div.render .ccm-page-list-title {
        font-size: 12px;
        font-weight: normal;
    }

    div.pagelist-form label.checkbox,
    div.pagelist-form label.radio {
        font-weight: 300;
    }

</style>
<script type="application/javascript">
    Concrete.event.publish('pagelist.edit.open');
    $(function() {
        $('input[name=topicFilter]').on('change', function() {
            if ($(this).val() == 'related') {
                $('div[data-row=related-topic]').show();
                $('div[data-row=custom-topic]').hide();
            } else if ($(this).val() == 'custom') {
                $('div[data-row=custom-topic]').show();
                $('div[data-row=related-topic]').hide();
            } else {
                $('div[data-row=related-topic]').hide();
                $('div[data-row=custom-topic]').hide();
            }
        });

        var treeViewTemplate = $('.tree-view-template');

        $('select[name=customTopicAttributeKeyHandle]').on('change', function() {
            var toolsURL = '<?php echo Loader::helper('concrete/urls')->getToolsURL('tree/load'); ?>';
            var chosenTree = $(this).find('option:selected').attr('data-topic-tree-id');
            $('.tree-view-template').remove();
            if (!chosenTree) {
                return;
            }
            $('.tree-view-container').append(treeViewTemplate);
            $('.tree-view-template').concreteTree({
                'treeID': chosenTree,
                'chooseNodeInForm': true,
                'selectNodesByKey': [<?php echo intval($customTopicTreeNodeID)?>],
                'onSelect' : function(nodes) {
                    if (nodes.length) {
                        $('input[name=customTopicTreeNodeID]').val(nodes[0]);
                    } else {
                        $('input[name=customTopicTreeNodeID]').val('');
                    }
                }
            });
        });
        $('input[name=topicFilter]:checked').trigger('change');
        if ($('#topicFilterCustom').is(':checked')) {
            $('select[name=customTopicAttributeKeyHandle]').trigger('change');
        }
    });

</script>

