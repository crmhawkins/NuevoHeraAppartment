@extends('layouts.appAdmin')

@section('content')
<div class="container-fluid">
    <div class="d-flex flex-column mb-3">
        <h2 class="mb-0 me-3 encabezado_top">{{ __('Crear Propiedad') }}</h2>
    </div>
    <hr>
    <div class="row justify-content-center">
        <div class="col-md-12">
            <form action="{{ route('channex.storeProperty') }}" method="POST" enctype="multipart/form-data">
                @if ($errors->any())
                    <div class="alert alert-danger">
                        <ul>
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @csrf
                <div class="mb-3">
                    <label for="title" class="form-label">Título</label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}">
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Moneda -->
                <div class="mb-3">
                    <label for="currency" class="form-label">Moneda</label>
                    <select class="form-select @error('currency') is-invalid @enderror" id="currency" name="currency">
                        <option value="EUR" selected>EUR - Euro</option>
                        <option value="USD">USD - US Dollar</option>
                        <option value="GBP">GBP - British Pound</option>
                        <option value="JPY">JPY - Japanese Yen</option>
                        <option value="AUD">AUD - Australian Dollar</option>
                    </select>
                    @error('currency')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- País -->
                <div class="mb-3">
                    <label for="country" class="form-label">País</label>
                    <select class="form-select @error('country') is-invalid @enderror" id="country" name="country">
                        <option value="ES" selected>España</option>
                        <option value="FR">Francia</option>
                        <option value="IT">Italia</option>
                        <option value="DE">Alemania</option>
                        <option value="US">Estados Unidos</option>
                    </select>
                    @error('country')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Estado/Provincia -->
                <div class="mb-3">
                    <label for="state" class="form-label">Estado/Provincia</label>
                    <select class="form-select @error('state') is-invalid @enderror" id="state" name="state">
                        <option value="Cádiz" selected>Cádiz</option>
                        <option value="Málaga">Málaga</option>
                        <option value="Sevilla">Sevilla</option>
                        <option value="Granada">Granada</option>
                        <option value="Madrid">Madrid</option>
                    </select>
                    @error('state')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Zona Horaria -->
                <div class="mb-3">
                    <label for="timezone" class="form-label">Zona Horaria</label>
                    <select class="form-select @error('timezone') is-invalid @enderror" id="timezone" name="timezone">
                        <option value="Europe/Madrid" selected>Europe/Madrid</option>
                        <option value="Europe/Paris">Europe/Paris</option>
                        <option value="Europe/Rome">Europe/Rome</option>
                        <option value="Europe/London">Europe/London</option>
                        <option value="America/New_York">America/New_York</option>
                    </select>
                    @error('timezone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Tipo de Propiedad -->
                <div class="mb-3">
                    <label for="property_type" class="form-label">Tipo de Propiedad</label>
                    <select class="form-select @error('property_type') is-invalid @enderror" id="property_type" name="property_type">
                        <option value="apartment" selected>Apartamento</option>
                        <option value="hotel">Hotel</option>
                        <option value="hostel">Hostel</option>
                        <option value="villa">Villa</option>
                        <option value="guest_house">Casa de Huéspedes</option>
                    </select>
                    @error('property_type')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Dirección, Ciudad, Código Postal, Longitud y Latitud -->
                <div class="mb-3">
                    <label for="address" class="form-label">Dirección</label>
                    <input type="text" class="form-control @error('address') is-invalid @enderror" id="address" name="address" value="{{ old('address') }}">
                    @error('address')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="city" class="form-label">Ciudad</label>
                    <input type="text" class="form-control @error('city') is-invalid @enderror" id="city" name="city" value="{{ old('city') }}">
                    @error('city')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="zip_code" class="form-label">Código Postal</label>
                    <input type="text" class="form-control @error('zip_code') is-invalid @enderror" id="zip_code" name="zip_code" value="{{ old('zip_code') }}">
                    @error('zip_code')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="longitude" class="form-label">Longitud</label>
                    <input type="text" class="form-control @error('longitude') is-invalid @enderror" id="longitude" name="longitude" value="{{ old('longitude') }}" readonly>
                    @error('longitude')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <div class="mb-3">
                    <label for="latitude" class="form-label">Latitud</label>
                    <input type="text" class="form-control @error('latitude') is-invalid @enderror" id="latitude" name="latitude" value="{{ old('latitude') }}" readonly>
                    @error('latitude')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}">
                    @error('email')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Teléfono -->
                <div class="mb-3">
                    <label for="phone" class="form-label">Teléfono</label>
                    <input type="text" class="form-control @error('phone') is-invalid @enderror" id="phone" name="phone" value="{{ old('phone') }}">
                    @error('phone')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Website -->
                <div class="mb-3">
                    <label for="website" class="form-label">Sitio Web</label>
                    <input type="url" class="form-control @error('website') is-invalid @enderror" id="website" name="website" value="{{ old('website') }}">
                    @error('website')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Descripción -->
                <div class="mb-3">
                    <label for="description" class="form-label">Descripción</label>
                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description">{{ old('description') }}</textarea>
                    @error('description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Información Importante -->
                <div class="mb-3">
                    <label for="important_information" class="form-label">Información Importante</label>
                    <textarea class="form-control @error('important_information') is-invalid @enderror" id="important_information" name="important_information">{{ old('important_information') }}</textarea>
                    @error('important_information')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>

                 <!-- Photos -->
                 <div class="mb-3">
                    <label class="form-label">Fotos</label>
                    <div id="photo-container">
                        <div class="photo-group mb-3">
                            <input type="file" class="form-control mb-2 @error('photos.0.file') is-invalid @enderror" name="photos[0][file]" accept="image/*">
                            <input type="number" class="form-control mb-2" name="photos[0][position]" placeholder="Posición" value="0">
                            <input type="text" class="form-control mb-2" name="photos[0][author]" placeholder="Autor">
                            <input type="text" class="form-control mb-2" name="photos[0][kind]" placeholder="Tipo (e.g., photo)">
                            <textarea class="form-control" name="photos[0][description]" placeholder="Descripción"></textarea>
                        </div>
                    </div>
                    <button type="button" class="btn btn-secondary" id="add-photo">Añadir Foto</button>
                    @error('photos.*.file')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>


                <div id="map" style="height: 400px;" class="mb-3"></div>

                <button type="submit" class="btn btn-terminar w-100 fs-4 mt-4">Guardar</button>
            </form>
        </div>
    </div>
</div>

<!-- Google Maps Script -->
<script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyCjf5b7p8WO6CTTNVnfnv3Xrjz10u-Y74g"></script>
<script>
    let map;
    let marker;
    const geocoder = new google.maps.Geocoder();

    function initializeMap() {
        const defaultLocation = { lat: 40.416775, lng: -3.703790 }; // Madrid como ubicación por defecto
        map = new google.maps.Map(document.getElementById("map"), {
            center: defaultLocation,
            zoom: 6,
        });

        marker = new google.maps.Marker({
            map: map,
            draggable: false,
        });
    }

    async function searchCoordinates() {
        const address = document.getElementById('address').value;
        const city = document.getElementById('city').value;
        const zipCode = document.getElementById('zip_code').value;

        if (address && city && zipCode) {
            const fullAddress = `${address}, ${city}, ${zipCode}`;
            geocoder.geocode({ address: fullAddress }, function (results, status) {
                if (status === "OK") {
                    const location = results[0].geometry.location;

                    document.getElementById('latitude').value = location.lat();
                    document.getElementById('longitude').value = location.lng();

                    map.setCenter(location);
                    map.setZoom(15);

                    marker.setPosition(location);
                } else {
                    alert("No se pudo encontrar la dirección: " + status);
                }
            });
        } else {
            alert('Por favor, complete Dirección, Ciudad y Código Postal antes de buscar.');
        }
    }

    document.getElementById('address').addEventListener('change', searchCoordinates);
    document.getElementById('city').addEventListener('change', searchCoordinates);
    document.getElementById('zip_code').addEventListener('change', searchCoordinates);

    window.onload = initializeMap;
</script>

<script>
    let photoIndex = 1;

    document.getElementById('add-photo').addEventListener('click', function () {
        const container = document.getElementById('photo-container');
        const group = document.createElement('div');
        group.classList.add('photo-group', 'mb-3');
        group.innerHTML = `
            <input type="file" class="form-control mb-2" name="photos[${photoIndex}][file]" accept="image/*">
            <input type="text" class="form-control mb-2" name="photos[${photoIndex}][position]" placeholder="Posición" value="${photoIndex}">
            <input type="text" class="form-control mb-2" name="photos[${photoIndex}][author]" placeholder="Autor">
            <input type="text" class="form-control mb-2" name="photos[${photoIndex}][kind]" placeholder="Tipo (e.g., photo)">
            <textarea class="form-control" name="photos[${photoIndex}][description]" placeholder="Descripción"></textarea>
        `;
        container.appendChild(group);
        photoIndex++;
    });
</script>
@endsection
