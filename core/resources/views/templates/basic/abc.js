(function ($) {
    "use strict";

    var date_of_journey = '';
    var pickup = '';
    var destination = '';

    if (date_of_journey && pickup && destination) {
        showBookedSeat();
    }



    //click on seat
    $('.seat-wrapper .seat').on('click', function () {
        var pickupPoint = $('select[name="pickup_point"]').val();
        var droppingPoing = $('select[name="dropping_point"]').val();

        if (pickupPoint && droppingPoing) {
            selectSeat();
        } else {
            $(this).removeClass('selected');
            notify('error', "@lang('Please select pickup point and dropping point before select any seat')")
        }
    });

    //select and booked seat


    //on change date, pickup point and destination point show available seats
    $(document).on('change', 'select[name="pickup_point"], select[name="dropping_point"], input[name="date_of_journey"]', function (e) {
        showBookedSeat();
    });

    //booked seat
    function showBookedSeat() {
        reset();
        var date = $('input[name="date_of_journey"]').val();
        var sourceId = $('select[name="pickup_point"]').find("option:selected").val();
        var destinationId = $('select[name="dropping_point"]').find("option:selected").val();

        if (sourceId == destinationId && destinationId != '') {
            notify('error', "@lang('Source Point and Destination Point Must Not Be Same')");
            $('select[name="dropping_point"]').val('').select2();
            return false;
        } else if (sourceId != destinationId) {

            var routeId = '{{ $trip-> route -> id}}';
            var fleetTypeId = '{{ $trip-> fleetType -> id}}';

            if (sourceId && destinationId) {
                getprice(routeId, fleetTypeId, sourceId, destinationId, date)
            }
        }
    }

    // check price, booked seat etc
    function getprice(routeId, fleetTypeId, sourceId, destinationId, date) {
        var data = {
            "trip_id": '{{ $trip-> id}}',
            "vehicle_route_id": routeId,
            "fleet_type_id": fleetTypeId,
            "source_id": sourceId,
            "destination_id": destinationId,
            "date": date,
        }
        $.ajax({
            type: "get",
            url: "{{ route('ticket.get-price') }}",
            data: data,
            success: function (response) {

                if (response.error) {
                    var modal = $('#alertModal');
                    modal.find('.error-message').text(response.error);
                    modal.modal('show');
                    $('select[name="pickup_point"]').val('');
                    $('select[name="dropping_point"]').val('');
                } else {
                    var stoppages = response.stoppages;

                    var reqSource = response.reqSource;
                    var reqDestination = response.reqDestination;

                    reqSource = stoppages.indexOf(reqSource.toString());
                    reqDestination = stoppages.indexOf(reqDestination.toString());

                    if (response.reverse == true) {
                        $.each(response.bookedSeats, function (i, v) {
                            var bookedSource = v.pickup_point; //Booked
                            var bookedDestination = v.dropping_point; //Booked

                            bookedSource = stoppages.indexOf(bookedSource.toString());
                            bookedDestination = stoppages.indexOf(bookedDestination.toString());

                            if (reqDestination >= bookedSource || reqSource <= bookedDestination) {
                                $.each(v.seats, function (index, val) {
                                    if (v.gender == 1) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().removeClass('seat-condition selected-by-gents disabled');
                                    }
                                    if (v.gender == 2) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().removeClass('seat-condition selected-by-ladies disabled');
                                    }
                                    if (v.gender == 3) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().removeClass('seat-condition selected-by-others disabled');
                                    }
                                });
                            } else {
                                $.each(v.seats, function (index, val) {
                                    if (v.gender == 1) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().addClass('seat-condition selected-by-gents disabled');
                                    }
                                    if (v.gender == 2) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().addClass('seat-condition selected-by-ladies disabled');
                                    }
                                    if (v.gender == 3) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().addClass('seat-condition selected-by-others disabled');
                                    }
                                });
                            }
                        });
                    } else {
                        $.each(response.bookedSeats, function (i, v) {
                            console.log(i, v);
                            var bookedSource = v.pickup_point; //Booked
                            var bookedDestination = v.dropping_point; //Booked

                            bookedSource = stoppages.indexOf(bookedSource.toString());
                            bookedDestination = stoppages.indexOf(bookedDestination.toString());


                            if (reqDestination <= bookedSource || reqSource >= bookedDestination) {
                                $.each(v.seats, function (index, val) {
                                    if (v.gender == 1) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().removeClass('seat-condition selected-by-gents disabled');
                                    }
                                    if (v.gender == 2) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().removeClass('seat-condition selected-by-ladies disabled');
                                    }
                                    if (v.gender == 3) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().removeClass('seat-condition selected-by-others disabled');
                                    }
                                });
                            } else {
                                $.each(v.seats, function (index, val) {
                                    if (v.gender == 1) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().addClass('seat-condition selected-by-gents disabled');
                                    }
                                    if (v.gender == 2) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().addClass('seat-condition selected-by-ladies disabled');
                                    }
                                    if (v.gender == 3) {
                                        $(`.seat-wrapper .seat[data-seat="${val}"]`).parent().addClass('seat-condition selected-by-others disabled');
                                    }
                                });
                            }
                        });
                    }

                    if (response.price.error) {
                        var modal = $('#alertModal');
                        modal.find('.error-message').text(response.price.error);
                        modal.modal('show');
                    } else {
                        $('input[name=price]').val(response.price);
                    }
                }
            }
        });
    }

    //booking form submit
    $('#bookingForm').on('submit', function (e) {
        e.preventDefault();
        let selectedSeats = $('.seat.selected');
        if (selectedSeats.length > 0) {
            var modal = $('#bookConfirm');
            modal.modal('show');
        } else {
            notify('error', 'Select at least one seat.');
        }
    });

    //confirmation modal
    $(document).on('click', '#btnBookConfirm', function (e) {
        var modal = $('#bookConfirm');
        modal.modal('hide');
        document.getElementById("bookingForm").submit();
    });

})(jQuery);

