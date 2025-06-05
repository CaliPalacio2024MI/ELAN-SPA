@extends('layout') 


@section('content')
<div class="container">
    <h2>Crear Nuevo Usuario</h2>

    @if (session('success'))
        <p style="color: green;">{{ session('success') }}</p>
    @endif

    @if (session('error'))
        <p style="color: red;">{{ session('error') }}</p>
    @endif

    <form action="{{ route('users.store') }}" method="POST">
        @csrf
        <label for="name">Nombre:</label>
        <input type="text" name="name" required>

        <label for="rfc">RFC:</label>
        <input type="text" name="rfc" required>

        <label for="rol">Rol:</label>
        <select name="rol">
            @foreach ($rolesDisponibles as $rol)
                <option value="{{ $rol }}">{{ ucfirst($rol) }}</option>
            @endforeach
        </select>

        <label for="area">Área:</label>
        <select name="area">
            <option value="palacio">Palacio</option>
            <option value="pierre">Pierre</option>
            <option value="princess">Princess</option>
        </select>

        <label for="password">Contraseña:</label>
        <input type="password" name="password" required>

        <button type="submit">Registrar Usuario</button>
    </form>
</div>
@endsection
