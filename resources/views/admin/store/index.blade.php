@extends('layouts.admin')

@section('title')
    Configuration de la Boutique
@endsection

@section('content-header')
    <h1>Configuration de la Boutique<small>Gérez les prix des ressources et les gains AFK.</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li class="active">Boutique</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <div class="col-md-4">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Paramètres Généraux</h3>
            </div>
            <form action="{{ route('admin.store.settings') }}" method="POST">
                @csrf
                <div class="box-body">
                    <div class="form-group">
                        <label for="afk_rate">Gain de Coins par minute (AFK)</label>
                        <input type="number" step="0.0001" name="afk_rate" class="form-control" value="{{ $afk_rate }}" />
                        <p class="text-muted small">Nombre de coins gagnés par l'utilisateur pour chaque minute passée sur le panel.</p>
                    </div>
                </div>
                <div class="box-footer">
                    <button type="submit" class="btn btn-primary pull-right">Mettre à jour</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-8">
        <div class="box">
            <div class="box-header with-border">
                <h3 class="box-title">Produits de la Boutique</h3>
                <div class="box-tools">
                    <button class="btn btn-sm btn-success" data-toggle="modal" data-target="#addProductModal">Ajouter un Produit</button>
                </div>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Nom</th>
                            <th>Type</th>
                            <th>Quantité</th>
                            <th>Prix</th>
                            <th>Statut</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($products as $product)
                            <tr>
                                <td>{{ $product->name }}</td>
                                <td><code>{{ strtoupper($product->type) }}</code></td>
                                <td>{{ $product->amount }}</td>
                                <td>{{ $product->price }} Coins</td>
                                <td>
                                    @if($product->enabled)
                                        <span class="label label-success">Activé</span>
                                    @else
                                        <span class="label label-danger">Désactivé</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('admin.store.delete', $product->id) }}" method="POST" style="display:inline;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-xs btn-danger"><i class="fa fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal Ajouter Produit -->
<div class="modal fade" id="addProductModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.store.new') }}" method="POST">
                @csrf
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                    <h4 class="modal-title">Ajouter un nouveau produit</h4>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nom du Produit</label>
                        <input type="text" name="name" class="form-control" required placeholder="Ex: Pack 1 Go RAM">
                    </div>
                    <div class="form-group">
                        <label>Description</label>
                        <input type="text" name="description" class="form-control" placeholder="Ex: Augmentez la mémoire de vos serveurs">
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Type de Ressource</label>
                                <select name="type" class="form-control">
                                    <option value="cpu">CPU (%)</option>
                                    <option value="memory">Mémoire (MB)</option>
                                    <option value="disk">Disque (MB)</option>
                                    <option value="backups">Backups (Slots)</option>
                                    <option value="databases">Bases de données (Slots)</option>
                                    <option value="slots">Slots de Serveur</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Quantité</label>
                                <input type="number" name="amount" class="form-control" required placeholder="1024">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Prix (Coins)</label>
                        <input type="number" step="0.01" name="price" class="form-control" required placeholder="50.00">
                    </div>
                    <div class="form-group">
                        <div class="checkbox checkbox-primary">
                            <input id="pEnabled" name="enabled" type="checkbox" value="1" checked>
                            <label for="pEnabled">Activé</label>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Annuler</button>
                    <button type="submit" class="btn btn-primary">Créer le produit</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
