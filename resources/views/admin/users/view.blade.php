@extends('layouts.admin')

@section('title')
    Manage User: {{ $user->username }}
@endsection

@section('content-header')
    <h1>{{ $user->name_first }} {{ $user->name_last}}<small>{{ $user->username }}</small></h1>
    <ol class="breadcrumb">
        <li><a href="{{ route('admin.index') }}">Admin</a></li>
        <li><a href="{{ route('admin.users') }}">Users</a></li>
        <li class="active">{{ $user->username }}</li>
    </ol>
@endsection

@section('content')
<div class="row">
    <form action="{{ route('admin.users.view', $user->id) }}" method="post">
        <div class="col-md-6">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Identity</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="email" class="control-label">Email</label>
                        <div>
                            <input type="email" name="email" value="{{ $user->email }}" class="form-control form-autocomplete-stop">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="registered" class="control-label">Username</label>
                        <div>
                            <input type="text" name="username" value="{{ $user->username }}" class="form-control form-autocomplete-stop">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="registered" class="control-label">Client First Name</label>
                        <div>
                            <input type="text" name="name_first" value="{{ $user->name_first }}" class="form-control form-autocomplete-stop">
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="registered" class="control-label">Client Last Name</label>
                        <div>
                            <input type="text" name="name_last" value="{{ $user->name_last }}" class="form-control form-autocomplete-stop">
                        </div>
                    </div>
                    <div class="form-group">
                        <label class="control-label">Default Language</label>
                        <div>
                            <select name="language" class="form-control">
                                @foreach($languages as $key => $value)
                                    <option value="{{ $key }}" @if($user->language === $key) selected @endif>{{ $value }}</option>
                                @endforeach
                            </select>
                            <p class="text-muted"><small>The default language to use when rendering the Panel for this user.</small></p>
                        </div>
                    </div>
                </div>
                <div class="box-footer">
                    {!! csrf_field() !!}
                    {!! method_field('PATCH') !!}
                    <input type="submit" value="Update User" class="btn btn-primary btn-sm">
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Password</h3>
                </div>
                <div class="box-body">
                    <div class="alert alert-success" style="display:none;margin-bottom:10px;" id="gen_pass"></div>
                    <div class="form-group no-margin-bottom">
                        <label for="password" class="control-label">Password <span class="field-optional"></span></label>
                        <div>
                            <input type="password" id="password" name="password" class="form-control form-autocomplete-stop">
                            <p class="text-muted small">Leave blank to keep this user's password the same. User will not receive any notification if password is changed.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="box">
                <div class="box-header with-border">
                    <h3 class="box-title">Permissions</h3>
                </div>
                <div class="box-body">
                    <div class="form-group">
                        <label for="root_admin" class="control-label">Administrator</label>
                        <div>
                            <select name="root_admin" class="form-control">
                                <option value="0">@lang('strings.no')</option>
                                <option value="1" {{ $user->root_admin ? 'selected="selected"' : '' }}>@lang('strings.yes')</option>
                            </select>
                            <p class="text-muted"><small>Setting this to 'Yes' gives a user full administrative access.</small></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-6">
             <div class="box box-info">
                 <div class="box-header with-border">
                     <h3 class="box-title">Store Resources</h3>
                 </div>
                 <div class="box-body">
                     <div class="row">
                        <div class="col-xs-12">
                             <div class="form-group">
                                 <label for="coins" class="control-label">Coins (Credits)</label>
                                 <input type="number" name="coins" value="{{ $user->coins }}" class="form-control" step="0.01">
                             </div>
                        </div>
                        <div class="col-xs-6">
                             <div class="form-group">
                                 <label for="bought_cpu" class="control-label">CPU Limit (%)</label>
                                 <input type="number" name="bought_cpu" value="{{ $user->bought_cpu }}" class="form-control">
                             </div>
                        </div>
                        <div class="col-xs-6">
                             <div class="form-group">
                                 <label for="bought_memory" class="control-label">Memory Limit (MB)</label>
                                 <input type="number" name="bought_memory" value="{{ $user->bought_memory }}" class="form-control">
                             </div>
                        </div>
                        <div class="col-xs-6">
                             <div class="form-group">
                                 <label for="bought_disk" class="control-label">Disk Limit (MB)</label>
                                 <input type="number" name="bought_disk" value="{{ $user->bought_disk }}" class="form-control">
                             </div>
                        </div>
                        <div class="col-xs-6">
                             <div class="form-group">
                                 <label for="bought_slots" class="control-label">Server Slots</label>
                                 <input type="number" name="bought_slots" value="{{ $user->bought_slots }}" class="form-control">
                             </div>
                        </div>
                        <div class="col-xs-6">
                             <div class="form-group">
                                 <label for="bought_databases" class="control-label">Database Limit</label>
                                 <input type="number" name="bought_databases" value="{{ $user->bought_databases }}" class="form-control">
                             </div>
                        </div>
                        <div class="col-xs-6">
                             <div class="form-group">
                                 <label for="bought_backups" class="control-label">Backup Limit</label>
                                 <input type="number" name="bought_backups" value="{{ $user->bought_backups }}" class="form-control">
                             </div>
                        </div>
                     </div>
                 </div>
             </div>
        </div>
    </form>
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Associated Servers</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Server Name</th>
                            <th>Node</th>
                            <th>Status</th>
                            <th>Resources (CPU / RAM / Disk)</th>
                            <th class="text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->servers as $server)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a>
                                    <br>
                                    <small>{{ $server->uuidShort }}</small>
                                </td>
                                <td>
                                    @if($server->node)
                                        <a href="{{ route('admin.nodes.view', $server->node->id) }}">{{ $server->node->name }}</a>
                                    @else
                                        <span class="text-muted">Unknown Node</span>
                                    @endif
                                </td>
                                <td>
                                    @if($server->suspended)
                                        <span class="label label-warning">Suspended</span>
                                    @elseif(!$server->installed_at)
                                        <span class="label label-info">Installing</span>
                                    @else
                                        <span class="label label-success">Active</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $server->cpu }}% / {{ $server->memory }}MB / {{ $server->disk }}MB
                                </td>
                                <td class="text-right">
                                    <a href="{{ route('admin.servers.view', $server->id) }}" class="btn btn-sm btn-primary">Manage</a>
                                </td>
                            </tr>
                        @endforeach
                        @if($user->servers->isEmpty())
                            <tr>
                                <td colspan="5" class="text-center">No servers associated with this user.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xs-12">
        <div class="box box-info">
            <div class="box-header with-border">
                <h3 class="box-title">Activity Logs (Last 10 Events)</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Action</th>
                            <th>Description</th>
                            <th>IP Address</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->activity->sortByDesc('created_at')->take(10) as $activity)
                            <tr>
                                <td>{{ $activity->event }}</td>
                                <td>{{ $activity->description }}</td>
                                <td>{{ $activity->ip }}</td>
                                <td>{{ $activity->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                        @if($user->activity->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center">No activity recorded.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xs-12">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">Delete User</h3>
            </div>
            <div class="box-body">
                <p class="no-margin">There must be no servers associated with this account in order for it to be deleted.</p>
            </div>
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">SSH Keys</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Fingerprint</th>
                            <th>Name</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->sshKeys as $key)
                            <tr>
                                <td><code>{{ $key->fingerprint }}</code></td>
                                <td>{{ $key->name }}</td>
                                <td>{{ $key->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                        @if($user->sshKeys->isEmpty())
                            <tr>
                                <td colspan="3" class="text-center">No SSH keys found.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    <div class="col-xs-12">
        <div class="box box-primary">
            <div class="box-header with-border">
                <h3 class="box-title">Servers (Subuser Access)</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Server Name</th>
                            <th>Owner</th>
                            <th>Node</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->accessibleServers()->where('owner_id', '!=', $user->id)->get() as $server)
                            <tr>
                                <td>
                                    <a href="{{ route('admin.servers.view', $server->id) }}">{{ $server->name }}</a>
                                    <br>
                                    <small>{{ $server->uuidShort }}</small>
                                </td>
                                <td>
                                    <a href="{{ route('admin.users.view', $server->owner_id) }}">{{ $server->user->username ?? 'Unknown' }}</a>
                                </td>
                                <td>
                                    @if($server->node)
                                        <a href="{{ route('admin.nodes.view', $server->node->id) }}">{{ $server->node->name }}</a>
                                    @else
                                        Unknown
                                    @endif
                                </td>
                                <td>
                                    <a href="{{ route('admin.servers.view', $server->id) }}" class="btn btn-sm btn-primary">Manage</a>
                                </td>
                            </tr>
                        @endforeach
                        @if($user->accessibleServers()->where('owner_id', '!=', $user->id)->count() === 0)
                            <tr>
                                <td colspan="4" class="text-center">No external server access.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xs-12">
        <div class="box box-warning">
            <div class="box-header with-border">
                <h3 class="box-title">API Keys</h3>
            </div>
            <div class="box-body table-responsive no-padding">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>Description</th>
                            <th>Identifier</th>
                            <th>Last Used</th>
                            <th>Created</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($user->apiKeys as $key)
                            <tr>
                                <td>{{ $key->memo }}</td>
                                <td><code>{{ $key->identifier }}</code></td>
                                <td>{{ $key->last_used_at ? $key->last_used_at->diffForHumans() : 'Never' }}</td>
                                <td>{{ $key->created_at->diffForHumans() }}</td>
                            </tr>
                        @endforeach
                        @if($user->apiKeys->isEmpty())
                            <tr>
                                <td colspan="4" class="text-center">No API keys active.</td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-xs-12">
        <div class="box box-danger">
            <div class="box-header with-border">
                <h3 class="box-title">Delete User</h3>
            </div>
            <div class="box-body">
                <p class="no-margin">There must be no servers associated with this account in order for it to be deleted.</p>
            </div>
            <div class="box-footer">
                <form action="{{ route('admin.users.view', $user->id) }}" method="POST">
                    {!! csrf_field() !!}
                    {!! method_field('DELETE') !!}
                    <input id="delete" type="submit" class="btn btn-sm btn-danger pull-right" {{ $user->servers->count() < 1 ?: 'disabled' }} value="Delete User" />
                </form>
                <form action="{{ route('admin.users.suspend', $user->id) }}" method="POST">
                     {!! csrf_field() !!}
                     <input type="submit" class="btn btn-sm btn-warning pull-left" style="margin-right: 10px;" value="{{ $user->suspended ? 'Unsuspend' : 'Suspend' }} User" />
                </form>
                <form action="{{ route('admin.users.login', $user->id) }}" method="POST">
                     {!! csrf_field() !!}
                     <input type="submit" class="btn btn-sm btn-success pull-left" style="margin-right: 10px;" value="Login As User" />
                </form>
                @if($user->use_totp)
                <form action="{{ route('admin.users.2fa', $user->id) }}" method="POST">
                     {!! csrf_field() !!}
                     <input type="submit" class="btn btn-sm btn-danger pull-left" value="Disable 2FA" />
                </form>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
