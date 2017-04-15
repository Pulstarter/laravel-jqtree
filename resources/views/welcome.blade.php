<!DOCTYPE html>
<html lang="{{ config('app.locale') }}" xmlns="http://www.w3.org/1999/html">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <title>Laravel</title>

    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>

    <link href="/css/app.css" rel="stylesheet">
    <script src="/js/app.js"></script>

    <script src="/plugins/jqtree/tree.jquery.js"></script>
    <link rel="stylesheet" href="/plugins/jqtree/jqtree.css">

</head>
<body>
<div id="content">

    <div class="container">
        <h1>Tree</h1>
        <div class="form-group">

            <button class="btn btn-success" @click="getTree">Get tree</button>
            <button class="btn btn-primary" @click="addRoot">Add Root Category</button>
            <button class="btn btn-warning" @click="addSubcategory">Add subcategory</button>
            <button class="btn btn-danger" @click="deleteCategory">Delete category</button>

        </div>

        <hr>

        <div class="form-group">
            <div class="row">
                <div class="col-sm-3">
                    <div id="tree1" style="padding: 20px;"></div>
                </div>

                <div class="col-sm-9">
                    <h1>Selected node</h1>
                    <div class="form-group">
                        <button class="btn btn-success" @click="saveCategory">Save node</button>
                        <button class="btn btn-default" @click="getBreadCrumbs">Get breadcrumbs</button>
                    </div>

                    <div class="form-group" v-if="currentNode">
                        <label>Name</label>
                        <input class="form-control" v-model="currentNode.name">
                    </div>

                    <pre>@{{ currentNode }}</pre>
                </div>
            </div>
        </div>

        <hr>

        <pre>@{{ tree }}</pre>
    </div>

</div>

<script>
    new Vue({
        el: '#content',
        data: {
            can_drag: true,
            selected_node: 0,
            tree: []
        },
        computed: {
            currentNode: function () {

                function find(id, items) {
                    var i = 0, found;

                    for (; i < items.length; i++) {
                        if (items[i].id === id) {
                            return items[i];
                        } else if (_.isArray(items[i].children)) {
                            found = find(id, items[i].children);
                            if (found) {
                                return found;
                            }
                        }
                    }
                }

                return find(this.selected_node, this.tree);

            }
        },
        methods: {
            loadData: function () {
                var self = this;
                $('#tree1').tree('loadData', self.tree);
                var node = $('#tree1').tree('getNodeById', self.selected_node);
                $('#tree1').tree('selectNode', node);
            },
            getTree: function () {
                var self = this;

                axios.get('/getTree').then(function (res) {
                    self.tree = res.data;
                    self.loadData();
                }).catch(function (err) {
                    console.log(err);
                });
            },
            getBreadCrumbs: function () {
                var self = this;

                axios.get('/getBreadCrumbs?id=' + this.selected_node).then(function (res) {
                    console.log(res)
                }).catch(function (err) {
                    console.log(err);
                });
            },
            addRoot: function () {
                var self = this;
                axios.post('/addCategory', {parent_id: 0}).then(function (res) {
                    self.selected_node = res.data;
                    self.getTree();
                }).catch(function (err) {
                    console.log(err);
                });
            },
            addSubcategory: function () {
                var self = this;
                axios.post('/addCategory', {parent_id: this.selected_node}).then(function (res) {
                    self.selected_node = res.data;
                    self.getTree();
                }).catch(function (err) {
                    console.log(err);
                });
            },
            saveCategory: function () {
                var self = this;
                axios.post('/saveCategory', {node: this.currentNode}).then(function (res) {
                    self.getTree();
                }).catch(function (err) {
                    console.log(err);
                });
            },
            deleteCategory: function () {

                if (confirm('Really delete?')) {
                    var self = this;
                    axios.post('/deleteCategory', {node: this.currentNode}).then(function (res) {
                        self.getTree();
                    }).catch(function (err) {
                        console.log(err);
                    });
                }

            },
            updateRootCategory: function (newRootNode, positions) {
                var self = this;
                axios.post('/updateRootCategory', {
                    node: self.currentNode,
                    rootNodeId: newRootNode,
                    positions: self.getPositions()
                }).then(function (res) {
                    self.getTree();
                }).catch(function (err) {
                    console.log(err);
                });
            },
            getPositions: function () {
                var position = 1;
                var positions = [];
                $('#tree1').tree('getTree').iterate(function (node) {
                    positions.push({'id': node.id, 'position': position++});
                    return true;
                });
                return positions;
            }
        },
        created: function () {

            var self = this;

            $(document).ready(function () {
                $('#tree1').tree({
                    data: [],
                    autoOpen: true,
                    dragAndDrop: true,
                    selectable: true
                });

                self.getTree();

                $('#tree1').bind(
                        'tree.move',
                        function (event) {
                            event.preventDefault();

                            if (self.can_drag) {
                                event.move_info.do_move();

                                self.selected_node = event.move_info.moved_node.id;
                                var newRootNode = event.move_info.target_node;

                                if (event.move_info.position == 'after') {
                                    newRootNode = event.move_info.target_node.parent;
                                }

                                if (event.move_info.position == 'before') {
                                    newRootNode = event.move_info.target_node.parent;
                                }

                                var newRootNodeId = (newRootNode) ? newRootNode.id : 0;

                                self.updateRootCategory(newRootNodeId);

                                //console.log('moved_node', event.move_info.moved_node);
                                //console.log('target_node', event.move_info.target_node);
                                //console.log('position', event.move_info.position);
                                //console.log('previous_parent', event.move_info.previous_parent);
                            }
                        }
                );

                $('#tree1').on(
                        'tree.select',
                        function (event) {
                            if (event.node) {
                                // node was selected
                                var node = event.node;
                                self.selected_node = node.id;
                            }
                            else {
                                // event.node is null
                                // a node was deselected
                                // e.previous_node contains the deselected node
                            }
                        }
                );
            });

        }
    });
</script>

</body>
</html>
