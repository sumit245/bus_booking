@extends('admin.layouts.app')

@section('panel')
<div class="container-fluid">
    <h4 class="mb-4">{{ $pageTitle ?? 'Add New Bus' }}</h4>

    <div class="card">
        <div class="card-body">
            <!-- Tab Navigation -->
            <ul class="nav nav-tabs" id="busTab" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" id="basic-tab" data-bs-toggle="tab" href="#basic" role="tab">Basic Details</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="seat-tab" data-bs-toggle="tab" href="#seat" role="tab">Seat Layout</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="boarding-tab" data-bs-toggle="tab" href="#boarding" role="tab">Boarding & Dropping Points</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="media-tab" data-bs-toggle="tab" href="#media" role="tab">Add Media</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" id="preview-tab" data-bs-toggle="tab" href="#preview" role="tab">Preview & Continue</a>
                </li>
            </ul>

            <form id="addBusForm">
                <div class="tab-content mt-3" id="busTabContent">

                    <!-- Basic Details -->
                    <div class="tab-pane fade show active" id="basic" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Arrival Time</label>
                                <input type="time" class="form-control" name="ArrivalTime">
                            </div>
                            <div class="col-md-6">
                                <label>Departure Time</label>
                                <input type="time" class="form-control" name="DepartureTime">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>From City</label>
                                <select class="form-control" name="FromCity">
                                    <option value="">Select City</option>
                                    <option value="Satna">Satna</option>
                                    <option value="Rewa">Rewa</option>
                                    <option value="Bhopal">Bhopal</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>To City</label>
                                <select class="form-control" name="ToCity">
                                    <option value="">Select City</option>
                                    <option value="Satna">Satna</option>
                                    <option value="Rewa">Rewa</option>
                                    <option value="Bhopal">Bhopal</option>
                                </select>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Bus Type</label>
                                <select class="form-control" name="BusType">
                                    <option value="AC">AC</option>
                                    <option value="Non AC">Non AC</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label>Service Name</label>
                                <input type="text" class="form-control" name="ServiceName" placeholder="Seat Seller">
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Travel Name</label>
                                <input type="text" class="form-control" name="TravelName" placeholder="Mbbs Bus Service">
                            </div>
                            <div class="col-md-6">
                                <label>Operator Name</label>
                                <select class="form-control" name="OperatorName">
                                    <option value="Operator 1">Operator 1</option>
                                    <option value="Operator 2">Operator 2</option>
                                    <option value="Operator 3">Operator 3</option>
                                </select>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="IdProofRequired"> Require ID Proof
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="IsDropPointMandatory"> Drop Point Mandatory
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="LiveTrackingAvailable"> Live Tracking Available
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="MTicketEnabled"> M-Ticket Enabled
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="PartialCancellationAllowed"> Partial Cancellation Allowed
                        </div>
                        <div class="mt-3">
                            <button type="button" class="btn btn-primary float-end next-tab">Next</button>
                        </div>
                    </div>

                    <!-- Seat Layout -->
                    <div class="tab-pane fade" id="seat" role="tabpanel">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Number of Decks</label>
                                <input type="number" min="1" max="2" class="form-control" id="numDecks" name="Decks">
                            </div>
                            <div class="col-md-6">
                                <label>Seat Layout</label>
                                <select class="form-control" name="SeatLayout">
                                    <option value="2x1">2x1</option>
                                    <option value="2x2">2x2</option>
                                    <option value="2x3">2x3</option>
                                </select>
                            </div>
                        </div>
                        <div id="deckSeatsContainer"></div>

                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label>Seat Type</label>
                                <select class="form-control" id="seatType" name="SeatType">
                                    <option value="Seater">Select Type</option>
                                    <option value="Seater">Seater</option>
                                    <option value="Sleeper">Sleeper</option>
                                    <option value="Both">Both</option>
                                </select>
                            </div>
                        </div>

                        <div id="seatPriceContainer">
                            <!-- dynamic seat price fields will be shown here -->
                        </div>

                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-secondary prev-tab">Previous</button>
                            <button type="button" class="btn btn-primary next-tab">Next</button>
                        </div>
                    </div>

                    <!-- Boarding & Dropping -->
                    <div class="tab-pane fade" id="boarding" role="tabpanel">
                        <h6>Boarding Points</h6>
                        <div id="boarding-points" class="mb-3">
                            <div class="d-flex mb-2 boarding-row">
                                <input type="text" class="form-control me-2" placeholder="Boarding Point Name" name="BoardingPointName[]">
                                <input type="time" class="form-control me-2" name="BoardingTime[]">
                            </div>
                        </div>
                        <button type="button" id="addBoarding" class="btn btn-sm btn-outline-primary mb-3">+ Add Boarding Point</button>

                        <h6>Dropping Points</h6>
                        <div id="dropping-points" class="mb-3">
                            <div class="d-flex mb-2 dropping-row">
                                <input type="text" class="form-control me-2" placeholder="Dropping Point Name" name="DroppingPointName[]">
                                <input type="time" class="form-control me-2" name="DroppingTime[]">
                            </div>
                        </div>
                        <button type="button" id="addDropping" class="btn btn-sm btn-outline-primary mb-3">+ Add Dropping Point</button>

                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-secondary prev-tab">Previous</button>
                            <button type="button" class="btn btn-primary next-tab">Next</button>
                        </div>
                    </div>

                    <!-- Media -->
                    <div class="tab-pane fade" id="media" role="tabpanel">
                        <h6>Add Bus Photos</h6>
                        <input type="file" class="form-control mb-3" name="BusPhotos[]" multiple>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-secondary prev-tab">Previous</button>
                            <button type="button" class="btn btn-primary next-tab">Next</button>
                        </div>
                    </div>

                    <!-- Preview -->
                    <div class="tab-pane fade" id="preview" role="tabpanel">
                        <h6>Preview Your Bus Details</h6>
                        <div id="previewContent" class="mb-3"></div>
                        <div class="d-flex justify-content-between mt-3">
                            <button type="button" class="btn btn-secondary prev-tab">Previous</button>
                            <a href="{{ route('admin.fleet.buses') }}" class="btn btn-success">Save & Close</a>
                        </div>
                    </div>

                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Tab navigation
    document.querySelectorAll('.next-tab').forEach(btn => {
        btn.addEventListener('click', function () {
            let activeTab = document.querySelector('.nav-tabs .nav-link.active');
            let nextTab = activeTab.parentElement.nextElementSibling?.querySelector('.nav-link');
            if (nextTab) {
                let tab = new bootstrap.Tab(nextTab);
                tab.show();
                if (nextTab.id === "preview-tab") {
                    generatePreview();
                }
            }
        });
    });
    document.querySelectorAll('.prev-tab').forEach(btn => {
        btn.addEventListener('click', function () {
            let activeTab = document.querySelector('.nav-tabs .nav-link.active');
            let prevTab = activeTab.parentElement.previousElementSibling?.querySelector('.nav-link');
            if (prevTab) {
                let tab = new bootstrap.Tab(prevTab);
                tab.show();
            }
        });
    });

    // Dynamic Deck Seats
    document.getElementById('numDecks').addEventListener('input', function () {
        const container = document.getElementById('deckSeatsContainer');
        container.innerHTML = '';
        let num = parseInt(this.value);
        for (let i = 1; i <= num; i++) {
            container.innerHTML += `
                <div class="mb-3">
                    <label>Seats in Deck ${i}</label>
                    <input type="number" class="form-control" name="DeckSeats[${i}]">
                </div>`;
        }
    });

    // Seat type selection
    document.getElementById('seatType').addEventListener('change', function () {
        const container = document.getElementById('seatPriceContainer');
        container.innerHTML = '';
        if (this.value === 'Seater') {
            container.innerHTML = `
                <div class="mb-3">
                    <label>Seater Price</label>
                    <input type="number" class="form-control" name="SeaterPrice">
                </div>`;
        } else if (this.value === 'Sleeper') {
            container.innerHTML = `
                <div class="mb-3">
                    <label>Sleeper Price</label>
                    <input type="number" class="form-control" name="SleeperPrice">
                </div>`;
        } else if (this.value === 'Both') {
            container.innerHTML = `
                <div class="mb-3">
                    <label>Seater Price</label>
                    <input type="number" class="form-control" name="SeaterPrice">
                </div>
                <div class="mb-3">
                    <label>Sleeper Price</label>
                    <input type="number" class="form-control" name="SleeperPrice">
                </div>`;
        }
    });

    // Boarding points
    document.getElementById('addBoarding').addEventListener('click', function () {
        let container = document.getElementById('boarding-points');
        let div = document.createElement('div');
        div.classList.add('d-flex', 'mb-2', 'boarding-row');
        div.innerHTML = `
            <input type="text" class="form-control me-2" placeholder="Boarding Point Name" name="BoardingPointName[]">
            <input type="time" class="form-control me-2" name="BoardingTime[]">
            <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
        `;
        container.appendChild(div);
    });

    // Dropping points
    document.getElementById('addDropping').addEventListener('click', function () {
        let container = document.getElementById('dropping-points');
        let div = document.createElement('div');
        div.classList.add('d-flex', 'mb-2', 'dropping-row');
        div.innerHTML = `
            <input type="text" class="form-control me-2" placeholder="Dropping Point Name" name="DroppingPointName[]">
            <input type="time" class="form-control me-2" name="DroppingTime[]">
            <button type="button" class="btn btn-sm btn-danger remove-row">X</button>
        `;
        container.appendChild(div);
    });

    // Remove row functionality
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('remove-row')) {
            e.target.parentElement.remove();
        }
    });

    // Generate Preview
    function generatePreview() {
        const form = document.getElementById('addBusForm');
        const data = new FormData(form);
        let html = '<ul class="list-group">';
        data.forEach((value, key) => {
            html += `<li class="list-group-item"><strong>${key}:</strong> ${value}</li>`;
        });
        html += '</ul>';
        document.getElementById('previewContent').innerHTML = html;
    }
</script>
@endsection
