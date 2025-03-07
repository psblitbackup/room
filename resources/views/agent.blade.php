@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    <h3>Agent</h3>

                    <div class="container-xl">
                        <div class="row row-cards">
                            <div class="col-12">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h3 class="card-title">My Chat Sessions</h3>
                                        {{-- <div class="btn-group" role="group">
                                            <button type="button"
                                                class="btn btn-success status-toggle {{ auth()->user()->chatAgent->status === 'online' ? 'active' : '' }}"
                                                data-status="online">Online</button>
                                            <button type="button"
                                                class="btn btn-warning status-toggle {{ auth()->user()->chatAgent->status === 'busy' ? 'active' : '' }}"
                                                data-status="busy">Busy</button>
                                            <button type="button"
                                                class="btn btn-secondary status-toggle {{ auth()->user()->chatAgent->status === 'offline' ? 'active' : '' }}"
                                                data-status="offline">Offline</button>
                                        </div> --}}
                                    </div>
                                    <div class="table-responsive">
                                        <table class="table table-vcenter card-table">
                                            <thead>
                                                <tr>
                                                    <th>Visitor</th>
                                                    <th>Status</th>
                                                    <th>Started</th>
                                                    <th>Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @foreach ($chatSessions as $session)
                                                    <tr>
                                                        <td>{{ $session->visitor->name }}</td>
                                                        <td>
                                                            <span
                                                                class="badge bg-{{ $session->status === 'active' ? 'success' : 'warning text-dark' }}">
                                                                {{ $session->status }}
                                                            </span>
                                                        </td>
                                                        <td>{{ $session->created_at->diffForHumans() }}</td>
                                                        <td>
                                                            <a href="{{ route('agent.chat.show', $session) }}"
                                                                class="btn btn-sm btn-primary">
                                                                View
                                                            </a>
                                                            @if ($session->status === 'active')
                                                                <form action="{{ route('agent.chat.end', $session) }}" method="POST"
                                                                    class="d-inline">
                                                                    @csrf
                                                                    @method('PATCH')
                                                                    <button type="submit" class="btn btn-sm btn-danger">End</button>
                                                                </form>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                    <div class="card-footer d-flex align-items-center">
                                        {{ $chatSessions->links() }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
