!function(a){$urlField=a(".source-url");var b=a(".s3-key-id"),c=a(".s3-secret-key");$s3BucketSelect=a(".s3-bucket-select > select"),$s3RefreshBucketsBtn=a(".s3-refresh-buckets"),$s3RefreshBucketsSpinner=$s3RefreshBucketsBtn.parent().next().children(),$s3Region=a(".s3-region"),refreshingS3Buckets=!1,$s3RefreshBucketsBtn.click(function(){if(!$s3RefreshBucketsBtn.hasClass("disabled")){$s3RefreshBucketsBtn.addClass("disabled"),$s3RefreshBucketsSpinner.removeClass("hidden");var a={params:{keyId:b.val(),secret:c.val()},sourceType:"S3AssetSourceType",dataType:"bucketList"};Craft.postActionRequest("assetSources/loadSourceTypeData",a,function(a,b){if($s3RefreshBucketsBtn.removeClass("disabled"),$s3RefreshBucketsSpinner.addClass("hidden"),"success"==b)if(a.error)alert(a.error);else if(a.length>0){var c=$s3BucketSelect.val(),d=!1;refreshingS3Buckets=!0,$s3BucketSelect.prop("readonly",!1).empty();for(var e=0;e<a.length;e++)a[e].bucket==c&&(d=!0),$s3BucketSelect.append('<option value="'+a[e].bucket+'" data-url-prefix="'+a[e].urlPrefix+'" data-region="'+a[e].region+'">'+a[e].bucket+"</option>");d&&$s3BucketSelect.val(c),refreshingS3Buckets=!1,d||$s3BucketSelect.trigger("change")}})}}),$s3BucketSelect.change(function(){if(!refreshingS3Buckets){var a=$s3BucketSelect.children("option:selected");$urlField.val(a.data("url-prefix")),$s3Region.val(a.data("region"))}});var d=a(".rackspace-username"),e=a(".racskspace-api-key"),f=a(".rackspace-region-select > select"),g=a(".rackspace-container-select > select"),h=a(".rackspace-refresh-containers"),i=h.parent().next().children(),j=!1;h.click(function(){if(!h.hasClass("disabled")){h.addClass("disabled"),i.removeClass("hidden");var a={params:{username:d.val(),apiKey:e.val(),region:f.val()},sourceType:"RackspaceAssetSourceType",dataType:"containerList"};Craft.postActionRequest("assetSources/loadSourceTypeData",a,function(a,b){if(h.removeClass("disabled"),i.addClass("hidden"),"success"==b)if(a.error)alert(a.error);else if(a.length>0){var c=g.val(),d=!1;j=!0,g.prop("readonly",!1).empty();for(var e=0;e<a.length;e++)a[e].container==c&&(d=!0),g.append('<option value="'+a[e].container+'" data-urlprefix="'+a[e].urlPrefix+'">'+a[e].container+"</option>");d&&g.val(c),j=!1,d||g.trigger("change")}})}}),g.change(function(){if(!j){var a=g.children("option:selected");$urlField.val(a.data("urlprefix"))}});var k=a(".google-key-id"),l=a(".google-secret-key");$googleBucketSelect=a(".google-bucket-select > select"),$googleRefreshBucketsBtn=a(".google-refresh-buckets"),$googleRefreshBucketsSpinner=$googleRefreshBucketsBtn.parent().next().children(),refreshingGoogleBuckets=!1,$googleRefreshBucketsBtn.click(function(){if(!$googleRefreshBucketsBtn.hasClass("disabled")){$googleRefreshBucketsBtn.addClass("disabled"),$googleRefreshBucketsSpinner.removeClass("hidden");var a={params:{keyId:k.val(),secret:l.val()},sourceType:"GoogleCloudAssetSourceType",dataType:"bucketList"};Craft.postActionRequest("assetSources/loadSourceTypeData",a,function(a,b){if($googleRefreshBucketsBtn.removeClass("disabled"),$googleRefreshBucketsSpinner.addClass("hidden"),"success"==b)if(a.error)alert(a.error);else if(a.length>0){var c=$googleBucketSelect.val(),d=!1;refreshingGoogleBuckets=!0,$googleBucketSelect.prop("readonly",!1).empty();for(var e=0;e<a.length;e++)a[e].bucket==c&&(d=!0),$googleBucketSelect.append('<option value="'+a[e].bucket+'" data-url-prefix="'+a[e].urlPrefix+'">'+a[e].bucket+"</option>");d&&$googleBucketSelect.val(c),refreshingGoogleBuckets=!1,d||$googleBucketSelect.trigger("change")}})}}),$googleBucketSelect.change(function(){if(!refreshingGoogleBuckets){var a=$googleBucketSelect.children("option:selected");$urlField.val(a.data("url-prefix"))}});var m=function(){var b=a(this).parents(".field"),c=b.find(".expires-amount").val(),d=b.find(".expires-period select").val(),e=0==parseInt(c,10)||0==d.length?"":c+d;b.find("[type=hidden]").val(e)};a(".expires-amount").keyup(m).change(m),a(".expires-period select").change(m)}(jQuery);
//# sourceMappingURL=asseteditsourcesettings.js.map