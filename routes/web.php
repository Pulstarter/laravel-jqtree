<?php

use App\Category;
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/getTree', function () {
    return Category::orderBy('position', 'asc')->get()->toTree();
});
Route::get('/getBreadCrumbs', function (Request $request) {
    return $result = Category::find($request->id)->getAncestors();
});

Route::post('/addCategory', function (Request $request) {

    $parent_id = $request->parent_id;

    $cat = new Category();

    return $cat->create(['parent_id' => $parent_id, 'name' => 'New category', 'image' => ''])->id;

});

Route::post('/saveCategory', function (Request $request) {

    $id = $request->node['id'];

    $cat = Category::find($id);

    $cat->name = $request->node['name'];

    $cat->save();

    return;
});

Route::post('/deleteCategory', function (Request $request) {

    $id = $request->node['id'];

    Category::find($id)->delete();

    return;
});

Route::post('/updateRootCategory', function (Request $request) {

    $cat = Category::find($request->node['id']);

    $cat->parent_id = $request->rootNodeId;

    $cat->save();

    // Update positions
    foreach ($request->positions as $position) {
        Category::where('id', $position['id'])
            ->update(['position' => $position['position']]);
    }

    return;
});

Auth::routes();

Route::get('/home', 'HomeController@index');
