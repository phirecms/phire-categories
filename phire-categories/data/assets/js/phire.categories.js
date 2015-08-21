/**
 * Categories Module Scripts for Phire CMS 2
 */

phire.currentCategoryParentId  = '----';
phire.currentCategoryParentUri = '';

phire.changeCategoryUri = function() {
    var slug = jax('#slug').val();

    if ((jax('#category_parent_id').val() != phire.currentCategoryParentId) && (jax.cookie.load('phire') != '')) {
        phire.currentCategoryParentId = jax('#category_parent_id').val();
        var phireCookie = jax.cookie.load('phire');
        var path = phireCookie.base_path + phireCookie.app_uri;
        var json = jax.get(path + '/categories/json/' + jax('#category_parent_id').val());
        phire.currentCategoryParentUri = json.parent_uri;
    }

    var uri = phire.currentCategoryParentUri;

    if ((slug == '') && (uri == '')) {
        uri = '/';
    } else {
        if (uri == '/') {
            uri = uri + slug;
        } else {
            uri = uri + ((slug != '') ? '/' + slug : '');
        }
    }

    jax('#uri').val(uri);
    jax('#uri-span').val(uri);

    return false;
};

jax(document).ready(function(){
    if (jax('#categories-form')[0] != undefined) {
        jax('#checkall').click(function(){
            if (this.checked) {
                jax('#categories-form').checkAll(this.value);
            } else {
                jax('#categories-form').uncheckAll(this.value);
            }
        });
        jax('#categories-form').submit(function(){
            return jax('#categories-form').checkValidate('checkbox', true);
        });
    }
    if (jax('#category-form')[0] != undefined) {
        if (jax('#uri').val() != '') {
            jax('#uri-span').val(jax('#uri').val());
        }
    }
    if ((jax('#category_type')[0] != undefined) && (jax('#id')[0] != undefined) && (jax.cookie.load('phire') != '')) {
        var type = jax('#category_type').val();
        var id   = jax('#id').val();

        var phireCookie = jax.cookie.load('phire');

        var path = phireCookie.base_path + phireCookie.app_uri;
        var json = jax.get(path + '/categories/json/' + id + '/' + type);

        for (var field in json) {
            jax('#' + field).val(json[field]);
            jax('#' + field)[0].defaultValue = json[field];
        }
    }
});