/**
 * Categories Module Scripts for Phire CMS 2
 */

phire.changeCategoryUri = function() {
    var slug = jax('#slug').val();
    var uri  = '';

    if ((jax('#category_parent_id').val() != '----') && (jax.cookie.load('phire') != '')) {
        var phireCookie = jax.cookie.load('phire');
        var path = phireCookie.base_path + phireCookie.app_uri;
        var json = jax.get(path + '/categories/json/' + jax('#category_parent_id').val());
        uri = json.parent_uri;
    }

    if (slug == '') {
        uri = '/';
    } else {
        uri = uri + '/' + slug;
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
});