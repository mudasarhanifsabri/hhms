 @extends('layouts.app')

@section('content')



<div class="row">
                         <div class="col-xl-3 col-lg-12">
                              <div class="card">
                                   <div class="card-header border-bottom">
                                        <div>
                                             <h4 class="card-title">Units</h4>
                                             <p class="mb-0">Show Units</p>
                                        </div>
                                   </div>
                                   <div class="card-body border-light">
                                        <form>
                                             <label for="choices-single-groups" class="form-label">Unit Location</label>
                                             <div class="choices" data-type="select-one" tabindex="0" role="combobox" aria-autocomplete="list" aria-haspopup="true" aria-expanded="false"><div class="choices__inner"><select class="form-control choices__input" id="choices-single-groups" data-choices="" data-choices-groups="" data-placeholder="Select City" name="choices-single-groups" hidden="" tabindex="-1" data-choice="active"><option value="" data-custom-properties="[object Object]">Choose a city</option></select><div class="choices__list choices__list--single"><div class="choices__item choices__placeholder choices__item--selectable" data-item="" data-id="1" data-value="" data-custom-properties="[object Object]" aria-selected="true">Choose a city</div></div></div><div class="choices__list choices__list--dropdown" aria-expanded="false"><input type="search" name="search_terms" class="choices__input choices__input--cloned" autocomplete="off" autocapitalize="off" spellcheck="false" role="textbox" aria-autocomplete="list" aria-label="Choose a city" placeholder=""><div class="choices__list" role="listbox"><div id="choices--choices-single-groups-item-choice-1" class="choices__item choices__item--choice is-selected choices__placeholder choices__item--selectable is-highlighted" role="option" data-choice="" data-id="1" data-value="" data-select-text="Press to select" data-choice-selectable="" aria-selected="true">Choose a city</div><div class="choices__group " role="group" data-group="" data-id="1327645655691" data-value="CA"><div class="choices__heading">CA</div></div><div id="choices--choices-single-groups-item-choice-17" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="17" data-value="Montreal" data-select-text="Press to select" data-choice-selectable="">Montreal</div><div id="choices--choices-single-groups-item-choice-18" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="18" data-value="Toronto" data-select-text="Press to select" data-choice-selectable="">Toronto</div><div id="choices--choices-single-groups-item-choice-19" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="19" data-value="Vancouver" data-select-text="Press to select" data-choice-selectable="">Vancouver</div><div class="choices__group " role="group" data-group="" data-id="1508599020236" data-value="FR"><div class="choices__heading">FR</div></div><div id="choices--choices-single-groups-item-choice-6" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="6" data-value="Lyon" data-select-text="Press to select" data-choice-selectable="">Lyon</div><div id="choices--choices-single-groups-item-choice-7" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="7" data-value="Marseille" data-select-text="Press to select" data-choice-selectable="">Marseille</div><div id="choices--choices-single-groups-item-choice-5" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="5" data-value="Paris" data-select-text="Press to select" data-choice-selectable="">Paris</div><div class="choices__group " role="group" data-group="" data-id="1416143302684" data-value="SP"><div class="choices__heading">SP</div></div><div id="choices--choices-single-groups-item-choice-15" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="15" data-value="Barcelona" data-select-text="Press to select" data-choice-selectable="">Barcelona</div><div id="choices--choices-single-groups-item-choice-14" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="14" data-value="Madrid" data-select-text="Press to select" data-choice-selectable="">Madrid</div><div id="choices--choices-single-groups-item-choice-16" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="16" data-value="Malaga" data-select-text="Press to select" data-choice-selectable="">Malaga</div><div class="choices__group " role="group" data-group="" data-id="1413214239206" data-value="UK"><div class="choices__heading">UK</div></div><div id="choices--choices-single-groups-item-choice-4" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="4" data-value="Liverpool" data-select-text="Press to select" data-choice-selectable="">Liverpool</div><div id="choices--choices-single-groups-item-choice-2" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="2" data-value="London" data-select-text="Press to select" data-choice-selectable="">London</div><div id="choices--choices-single-groups-item-choice-3" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="3" data-value="Manchester" data-select-text="Press to select" data-choice-selectable="">Manchester</div><div class="choices__group " role="group" data-group="" data-id="1567976603254" data-value="US"><div class="choices__heading">US</div></div><div id="choices--choices-single-groups-item-choice-13" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="13" data-value="Michigan" data-select-text="Press to select" data-choice-selectable="">Michigan</div><div id="choices--choices-single-groups-item-choice-11" class="choices__item choices__item--choice choices__item--selectable" role="treeitem" data-choice="" data-id="11" data-value="New York" data-select-text="Press to select" data-choice-selectable="">New York</div><div id="choices--choices-single-groups-item-choice-12" class="choices__item choices__item--choice choices__item--disabled" role="treeitem" data-choice="" data-id="12" data-value="Washington" data-select-text="Press to select" data-choice-disabled="" aria-disabled="true">Washington</div></div></div></div>

                                             <div class="mb-3">
                                                  <label for="typeplace" class="form-label">Type Of Place</label>
                                                  <input type="text" id="typeplace" class="form-control">
                                             </div>
                                        </form>
                                        <h5 class="text-dark fw-medium my-3">Custom Price Range :</h5>
                                        <div id="product-price-range" [data-slider-size="md" ]="" class="noUi-target noUi-ltr noUi-horizontal noUi-txt-dir-ltr"><div class="noUi-base"><div class="noUi-connects"><div class="noUi-connect noUi-draggable" style="transform: translate(5%, 0px) scale(0.783333, 1);"></div></div><div class="noUi-origin" style="transform: translate(-95%, 0px); z-index: 5;"><div class="noUi-handle noUi-handle-lower" data-handle="0" tabindex="0" role="slider" aria-orientation="horizontal" aria-valuemin="0.0" aria-valuemax="100000.0" aria-valuenow="6000.0" aria-valuetext="$ 6000"><div class="noUi-touch-area"></div></div></div><div class="noUi-origin" style="transform: translate(-16.6667%, 0px); z-index: 4;"><div class="noUi-handle noUi-handle-upper" data-handle="1" tabindex="0" role="slider" aria-orientation="horizontal" aria-valuemin="6000.0" aria-valuemax="120000.0" aria-valuenow="100000.0" aria-valuetext="$ 100000"><div class="noUi-touch-area"></div></div></div></div></div>
                                        <div class="formCost d-flex gap-2 align-items-center mt-3">
                                             <input class="form-control form-control-sm text-center" type="text" id="minCost" value="0">
                                             <span class="fw-semibold text-muted">to</span>
                                             <input class="form-control form-control-sm text-center" type="text" id="maxCost" value="1000">
                                        </div>
                                        <h5 class="text-dark fw-medium my-3">Accessibility Features :</h5>
                                        <div class="row g-1">
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck11">
                                                       <label class="form-check-label ms-1" for="defaultCheck11">
                                                            For Rent
                                                       </label>
                                                  </div>
                                             </div>
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck12">
                                                       <label class="form-check-label ms-1" for="defaultCheck12">
                                                            For Sale
                                                       </label>
                                                  </div>
                                             </div>
                                        </div>
                                        <h5 class="text-dark fw-medium my-3">Unit Type :</h5>
                                        <div class="row g-1">
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck" checked="">
                                                       <label class="form-check-label ms-1" for="defaultCheck">
                                                            All Units
                                                       </label>
                                                  </div>
                                             </div>
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck1">
                                                       <label class="form-check-label ms-1" for="defaultCheck1">
                                                            Cottage
                                                       </label>
                                                  </div>
                                             </div>
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck2">
                                                       <label class="form-check-label ms-1" for="defaultCheck1">
                                                            Villas
                                                       </label>
                                                  </div>
                                             </div>
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck3">
                                                       <label class="form-check-label ms-1" for="defaultCheck1">
                                                            Apartment
                                                       </label>
                                                  </div>
                                             </div>
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck4">
                                                       <label class="form-check-label ms-1" for="defaultCheck1">
                                                            Duplex Bungalow
                                                       </label>
                                                  </div>
                                             </div>
                                        </div>
                                        <h5 class="text-dark fw-medium my-3">Bedrooms :</h5>
                                        <div class="" role="group" aria-label="Basic checkbox toggle button group">
                                             <input type="checkbox" class="btn-check" id="btncheck1">
                                             <label class="btn btn-outline-primary" for="btncheck1">1 BHK</label>

                                             <input type="checkbox" class="btn-check" id="btncheck2">
                                             <label class="btn btn-outline-primary" for="btncheck2">2 BHK</label>

                                             <input type="checkbox" class="btn-check" id="btncheck3" checked="">
                                             <label class="btn btn-outline-primary" for="btncheck3">3 BHK</label>

                                             <input type="checkbox" class="btn-check" id="btncheck4">
                                             <label class="btn btn-outline-primary my-1" for="btncheck4">4 &amp; 5 BHK</label>

                                        </div>
                                        <h5 class="text-dark fw-medium my-3">Accessibility Features :</h5>
                                        <div class="row g-1">
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck5">
                                                       <label class="form-check-label ms-1" for="defaultCheck1">
                                                            Balcony
                                                       </label>
                                                  </div>
                                             </div>
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck6">
                                                       <label class="form-check-label ms-1" for="defaultCheck1">
                                                            Parking
                                                       </label>
                                                  </div>
                                             </div>
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck7">
                                                       <label class="form-check-label ms-1" for="defaultCheck1">
                                                            Spa
                                                       </label>
                                                  </div>
                                             </div>
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck8">
                                                       <label class="form-check-label ms-1" for="defaultCheck1">
                                                            Pool
                                                       </label>
                                                  </div>
                                             </div>
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck9">
                                                       <label class="form-check-label ms-1" for="defaultCheck1">
                                                            Restaurant
                                                       </label>
                                                  </div>
                                             </div>
                                             <div class="col-lg-6">
                                                  <div class="mb-2">
                                                       <input class="form-check-input" type="checkbox" value="" id="defaultCheck10">
                                                       <label class="form-check-label ms-1" for="defaultCheck1">
                                                            Fitness Club
                                                       </label>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                                   <div class="card-footer">
                                        <a href="#!" class="btn btn-primary w-100">Apply</a>
                                   </div>
                              </div>
                         </div>

                         <div class="col-xl-9 col-lg-12">
                              <div class="row">
                                   <div class="col-lg-4 col-md-6">
                                        <div class="card overflow-hidden">
                                             <div class="position-relative">
                                                  <img src="assets/images/properties/p-1.jpg" alt="" class="img-fluid rounded-top">
                                                  <span class="position-absolute top-0 start-0 p-1">
                                                       <button type="button" class="btn btn-warning avatar-sm d-inline-flex align-items-center justify-content-center fs-20 rounded text-light"><iconify-icon icon="solar:bookmark-broken"></iconify-icon></button>
                                                  </span>
                                                  <span class="position-absolute top-0 end-0 p-1">
                                                       <span class="badge bg-success text-white fs-13">For Rent</span>
                                                  </span>
                                             </div>
                                             <div class="card-body">
                                                  <div class="d-flex align-items-center gap-2">
                                                       <div class="avatar bg-light rounded">
                                                            <iconify-icon icon="solar:home-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                                                       </div>
                                                       <div>
                                                            <a href="#!" class="text-dark fw-medium fs-16">Dvilla Residences Batu</a>
                                                            <p class="text-muted mb-0">4604 , Philli Lane
                                                                 Kiowa</p>
                                                       </div>
                                                  </div>
                                                  <div class="row mt-2 g-2">
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bed-broken" class="align-middle"></iconify-icon></span>
                                                                 5 Beds
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bath-broken" class="align-middle"></iconify-icon></span>
                                                                 4 Bath
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:scale-broken" class="align-middle"></iconify-icon></span>
                                                                 1400ft
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:double-alt-arrow-up-broken" class="align-middle"></iconify-icon></span>
                                                                 3 Floor
                                                            </span>
                                                       </div>
                                                  </div>

                                             </div>
                                             <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                                                  <p class="fw-medium text-dark fs-16 mb-0">$8,930.00 </p>
                                                  <div>
                                                       <a href="#!" class="link-primary fw-medium">More Inquiry <i class="ri-arrow-right-line align-middle"></i></a>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                                   <div class="col-lg-4 col-md-6">
                                        <div class="card overflow-hidden">
                                             <div class="position-relative">
                                                  <img src="assets/images/properties/p-2.jpg" alt="" class="img-fluid rounded-top">
                                                  <span class="position-absolute top-0 end-0 p-1">
                                                       <span class="badge bg-danger text-white fs-13">Sold</span>
                                                  </span>
                                             </div>
                                             <div class="card-body">
                                                  <div class="d-flex align-items-center gap-2">
                                                       <div class="avatar bg-light rounded">
                                                            <iconify-icon icon="solar:home-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                                                       </div>
                                                       <div>
                                                            <a href="#!" class="text-dark fw-medium fs-16">PIK Villa House</a>
                                                            <p class="text-muted mb-0">127, Boulevard Cockeysville</p>
                                                       </div>
                                                  </div>
                                                  <div class="row mt-2 g-2">
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bed-broken" class="align-middle"></iconify-icon></span>
                                                                 6 Beds
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bath-broken" class="align-middle"></iconify-icon></span>
                                                                 5 Bath
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:scale-broken" class="align-middle"></iconify-icon></span>
                                                                 1700ft
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:double-alt-arrow-up-broken" class="align-middle"></iconify-icon></span>
                                                                 3 Floor
                                                            </span>
                                                       </div>
                                                  </div>

                                             </div>
                                             <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                                                  <p class="fw-medium text-muted text-decoration-line-through fs-16 mb-0">$60,691.00 </p>
                                                  <div>
                                                       <a href="#!" class="link-primary fw-medium ">More Inquiry <i class="ri-arrow-right-line align-middle"></i></a>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                                   <div class="col-lg-4 col-md-6">
                                        <div class="card overflow-hidden">
                                             <div class="position-relative">
                                                  <img src="assets/images/properties/p-3.jpg" alt="" class="img-fluid rounded-top">
                                                  <span class="position-absolute top-0 start-0 p-1">
                                                       <button type="button" class="btn btn-warning avatar-sm d-inline-flex align-items-center justify-content-center fs-20 rounded text-light"><iconify-icon icon="solar:bookmark-broken"></iconify-icon></button>
                                                  </span>
                                                  <span class="position-absolute top-0 end-0 p-1">
                                                       <span class="badge bg-warning text-white fs-13">For Sale</span>
                                                  </span>
                                             </div>
                                             <div class="card-body">
                                                  <div class="d-flex align-items-center gap-2">
                                                       <div class="avatar bg-light rounded">
                                                            <iconify-icon icon="solar:home-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                                                       </div>
                                                       <div>
                                                            <a href="#!" class="text-dark fw-medium fs-16">Tungis Luxury</a>
                                                            <p class="text-muted mb-0">900 , Creside WI 54913</p>
                                                       </div>
                                                  </div>
                                                  <div class="row mt-2 g-2">
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bed-broken" class="align-middle"></iconify-icon></span>
                                                                 4 Beds
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bath-broken" class="align-middle"></iconify-icon></span>
                                                                 3 Bath
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:scale-broken" class="align-middle"></iconify-icon></span>
                                                                 1200ft
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:double-alt-arrow-up-broken" class="align-middle"></iconify-icon></span>
                                                                 2 Floor
                                                            </span>
                                                       </div>
                                                  </div>

                                             </div>
                                             <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                                                  <p class="fw-medium text-dark fs-16 mb-0">$70,245.00 </p>
                                                  <div>
                                                       <a href="#!" class="link-primary fw-medium">More Inquiry <i class="ri-arrow-right-line align-middle"></i></a>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                                   <div class="col-lg-4 col-md-6">
                                        <div class="card overflow-hidden">
                                             <div class="position-relative">
                                                  <img src="assets/images/properties/p-4.jpg" alt="" class="img-fluid rounded-top">
                                                  <span class="position-absolute top-0 start-0 p-1">
                                                       <button type="button" class="btn bg-warning-subtle avatar-sm d-inline-flex align-items-center justify-content-center fs-20 rounded text-warning"><iconify-icon icon="solar:bookmark-broken"></iconify-icon></button>
                                                  </span>
                                                  <span class="position-absolute top-0 end-0 p-1">
                                                       <span class="badge bg-success text-white fs-13">For Rent</span>
                                                  </span>
                                             </div>
                                             <div class="card-body">
                                                  <div class="d-flex align-items-center gap-2">
                                                       <div class="avatar bg-light rounded">
                                                            <iconify-icon icon="solar:buildings-3-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                                                       </div>
                                                       <div>
                                                            <a href="#!" class="text-dark fw-medium fs-16">Luxury Apartment</a>
                                                            <p class="text-muted mb-0">223 , Creside Santa Maria</p>
                                                       </div>
                                                  </div>
                                                  <div class="row mt-2 g-2">
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bed-broken" class="align-middle"></iconify-icon></span>
                                                                 2 Beds
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bath-broken" class="align-middle"></iconify-icon></span>
                                                                 2 Bath
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:scale-broken" class="align-middle"></iconify-icon></span>
                                                                 900ft
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:double-alt-arrow-up-broken" class="align-middle"></iconify-icon></span>
                                                                 1 Floor
                                                            </span>
                                                       </div>
                                                  </div>

                                             </div>
                                             <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                                                  <p class="fw-medium text-dark fs-16 mb-0">$5,825.00 </p>
                                                  <div>
                                                       <a href="#!" class="link-primary fw-medium">More Inquiry <i class="ri-arrow-right-line align-middle"></i></a>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                                   <div class="col-lg-4 col-md-6">
                                        <div class="card overflow-hidden">
                                             <div class="position-relative">
                                                  <img src="assets/images/properties/p-5.jpg" alt="" class="img-fluid rounded-top">
                                                  <span class="position-absolute top-0 start-0 p-1">
                                                       <button type="button" class="btn bg-warning-subtle avatar-sm d-inline-flex align-items-center justify-content-center fs-20 rounded text-warning"><iconify-icon icon="solar:bookmark-broken"></iconify-icon></button>
                                                  </span>
                                                  <span class="position-absolute top-0 end-0 p-1">
                                                       <span class="badge bg-warning text-white fs-13">For Sale</span>
                                                  </span>
                                             </div>
                                             <div class="card-body">
                                                  <div class="d-flex align-items-center gap-2">
                                                       <div class="avatar bg-light rounded">
                                                            <iconify-icon icon="solar:home-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                                                       </div>
                                                       <div>
                                                            <a href="#!" class="text-dark fw-medium fs-16">Weekend Villa MBH</a>
                                                            <p class="text-muted mb-0">980, Jim Rosa Lane Dublin</p>
                                                       </div>
                                                  </div>
                                                  <div class="row mt-2 g-2">
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bed-broken" class="align-middle"></iconify-icon></span>
                                                                 5 Beds
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bath-broken" class="align-middle"></iconify-icon></span>
                                                                 5 Bath
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:scale-broken" class="align-middle"></iconify-icon></span>
                                                                 1900ft
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:double-alt-arrow-up-broken" class="align-middle"></iconify-icon></span>
                                                                 2 Floor
                                                            </span>
                                                       </div>
                                                  </div>

                                             </div>
                                             <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                                                  <p class="fw-medium text-dark fs-16 mb-0">$90,674.00 </p>
                                                  <div>
                                                       <a href="#!" class="link-primary fw-medium">More Inquiry <i class="ri-arrow-right-line align-middle"></i></a>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                                   <div class="col-lg-4 col-md-6">
                                        <div class="card overflow-hidden">
                                             <div class="position-relative">
                                                  <img src="assets/images/properties/p-6.jpg" alt="" class="img-fluid rounded-top">
                                                  <span class="position-absolute top-0 start-0 p-1">
                                                       <button type="button" class="btn btn-warning avatar-sm d-inline-flex align-items-center justify-content-center fs-20 rounded text-light"><iconify-icon icon="solar:bookmark-broken"></iconify-icon></button>
                                                  </span>
                                                  <span class="position-absolute top-0 end-0 p-1">
                                                       <span class="badge bg-success text-white fs-13">For Rent</span>
                                                  </span>
                                             </div>
                                             <div class="card-body">
                                                  <div class="d-flex align-items-center gap-2">
                                                       <div class="avatar bg-light rounded">
                                                            <iconify-icon icon="solar:home-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                                                       </div>
                                                       <div>
                                                            <a href="#!" class="text-dark fw-medium fs-16">Luxury Penthouse</a>
                                                            <p class="text-muted mb-0">Sumner Street Los Angeles</p>
                                                       </div>
                                                  </div>
                                                  <div class="row mt-2 g-2">
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bed-broken" class="align-middle"></iconify-icon></span>
                                                                 7 Beds
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bath-broken" class="align-middle"></iconify-icon></span>
                                                                 6 Bath
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:scale-broken" class="align-middle"></iconify-icon></span>
                                                                 2000ft
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:double-alt-arrow-up-broken" class="align-middle"></iconify-icon></span>
                                                                 1 Floor
                                                            </span>
                                                       </div>
                                                  </div>

                                             </div>
                                             <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                                                  <p class="fw-medium text-dark fs-16 mb-0">$10,500.00 </p>
                                                  <div>
                                                       <a href="#!" class="link-primary fw-medium">More Inquiry <i class="ri-arrow-right-line align-middle"></i></a>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                                   <div class="col-lg-4 col-md-6">
                                        <div class="card overflow-hidden">
                                             <div class="position-relative">
                                                  <img src="assets/images/properties/p-7.jpg" alt="" class="img-fluid rounded-top">
                                                  <span class="position-absolute top-0 end-0 p-1">
                                                       <span class="badge bg-danger text-white fs-13">Sold</span>
                                                  </span>
                                             </div>
                                             <div class="card-body">
                                                  <div class="d-flex align-items-center gap-2">
                                                       <div class="avatar bg-light rounded">
                                                            <iconify-icon icon="solar:buildings-3-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                                                       </div>
                                                       <div>
                                                            <a href="#!" class="text-dark fw-medium fs-16">Ojiag Duplex House</a>
                                                            <p class="text-muted mb-0">24 Hanover, New York</p>
                                                       </div>
                                                  </div>
                                                  <div class="row mt-2 g-2">
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bed-broken" class="align-middle"></iconify-icon></span>
                                                                 3 Beds
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bath-broken" class="align-middle"></iconify-icon></span>
                                                                 3 Bath
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:scale-broken" class="align-middle"></iconify-icon></span>
                                                                 1300ft
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:double-alt-arrow-up-broken" class="align-middle"></iconify-icon></span>
                                                                 2 Floor
                                                            </span>
                                                       </div>
                                                  </div>

                                             </div>
                                             <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                                                  <p class="fw-medium text-muted text-decoration-line-through fs-16 mb-0">$75,341.00 </p>
                                                  <div>
                                                       <a href="#!" class="link-primary fw-medium">More Inquiry <i class="ri-arrow-right-line align-middle"></i></a>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                                   <div class="col-lg-4 col-md-6">
                                        <div class="card overflow-hidden">
                                             <div class="position-relative">
                                                  <img src="assets/images/properties/p-8.jpg" alt="" class="img-fluid rounded-top">
                                                  <span class="position-absolute top-0 start-0 p-1">
                                                       <button type="button" class="btn bg-warning-subtle avatar-sm d-inline-flex align-items-center justify-content-center fs-20 rounded text-warning"><iconify-icon icon="solar:bookmark-broken"></iconify-icon></button>
                                                  </span>
                                                  <span class="position-absolute top-0 end-0 p-1">
                                                       <span class="badge bg-warning text-white fs-13">For Sale</span>
                                                  </span>
                                             </div>
                                             <div class="card-body">
                                                  <div class="d-flex align-items-center gap-2">
                                                       <div class="avatar bg-light rounded">
                                                            <iconify-icon icon="solar:home-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                                                       </div>
                                                       <div>
                                                            <a href="#!" class="text-dark fw-medium fs-16">Luxury Bungalow Villas</a>
                                                            <p class="text-muted mb-0">Khale Florence, SC 219</p>
                                                       </div>
                                                  </div>
                                                  <div class="row mt-2 g-2">
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bed-broken" class="align-middle"></iconify-icon></span>
                                                                 4 Beds
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bath-broken" class="align-middle"></iconify-icon></span>
                                                                 3 Bath
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:scale-broken" class="align-middle"></iconify-icon></span>
                                                                 1200ft
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:double-alt-arrow-up-broken" class="align-middle"></iconify-icon></span>
                                                                 1 Floor
                                                            </span>
                                                       </div>
                                                  </div>

                                             </div>
                                             <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                                                  <p class="fw-medium text-dark fs-16 mb-0">$54,230.00 </p>
                                                  <div>
                                                       <a href="#!" class="link-primary fw-medium">More Inquiry <i class="ri-arrow-right-line align-middle"></i></a>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                                   <div class="col-lg-4 col-md-6">
                                        <div class="card overflow-hidden">
                                             <div class="position-relative">
                                                  <img src="assets/images/properties/p-9.jpg" alt="" class="img-fluid rounded-top">
                                                  <span class="position-absolute top-0 start-0 p-1">
                                                       <button type="button" class="btn bg-warning-subtle avatar-sm d-inline-flex align-items-center justify-content-center fs-20 rounded text-warning"><iconify-icon icon="solar:bookmark-broken"></iconify-icon></button>
                                                  </span>
                                                  <span class="position-absolute top-0 end-0 p-1">
                                                       <span class="badge bg-success text-white fs-13">For Rent</span>
                                                  </span>
                                             </div>
                                             <div class="card-body">
                                                  <div class="d-flex align-items-center gap-2">
                                                       <div class="avatar bg-light rounded">
                                                            <iconify-icon icon="solar:home-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                                                       </div>
                                                       <div>
                                                            <a href="#!" class="text-dark fw-medium fs-16">Duplex Bungalow</a>
                                                            <p class="text-muted mb-0">25, Willison Street
                                                                 Becker</p>
                                                       </div>
                                                  </div>
                                                  <div class="row mt-2 g-2">
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bed-broken" class="align-middle"></iconify-icon></span>
                                                                 3 Beds
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bath-broken" class="align-middle"></iconify-icon></span>
                                                                 3 Bath
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:scale-broken" class="align-middle"></iconify-icon></span>
                                                                 1800ft
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:double-alt-arrow-up-broken" class="align-middle"></iconify-icon></span>
                                                                 3 Floor
                                                            </span>
                                                       </div>
                                                  </div>

                                             </div>
                                             <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                                                  <p class="fw-medium text-dark fs-16 mb-0">$14,564.00 </p>
                                                  <div>
                                                       <a href="#!" class="link-primary fw-medium">More Inquiry <i class="ri-arrow-right-line align-middle"></i></a>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                                   <div class="col-lg-4 col-md-6">
                                        <div class="card overflow-hidden">
                                             <div class="position-relative">
                                                  <img src="assets/images/properties/p-10.jpg" alt="" class="img-fluid rounded-top">
                                                  <span class="position-absolute top-0 end-0 p-1">
                                                       <span class="badge bg-danger text-white fs-13">Sold</span>
                                                  </span>
                                             </div>
                                             <div class="card-body">
                                                  <div class="d-flex align-items-center gap-2">
                                                       <div class="avatar bg-light rounded">
                                                            <iconify-icon icon="solar:buildings-3-bold-duotone" class="fs-24 text-primary avatar-title"></iconify-icon>
                                                       </div>
                                                       <div>
                                                            <a href="#!" class="text-dark fw-medium fs-16">Woodis B. Apartment</a>
                                                            <p class="text-muted mb-0">Bungalow Road
                                                                 Niobrara</p>
                                                       </div>
                                                  </div>
                                                  <div class="row mt-2 g-2">
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bed-broken" class="align-middle"></iconify-icon></span>
                                                                 4 Beds
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:bath-broken" class="align-middle"></iconify-icon></span>
                                                                 3 Bath
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:scale-broken" class="align-middle"></iconify-icon></span>
                                                                 1700ft
                                                            </span>
                                                       </div>
                                                       <div class="col-lg-4 col-4">
                                                            <span class="badge bg-light-subtle text-muted border fs-12">
                                                                 <span class="fs-16"><iconify-icon icon="solar:double-alt-arrow-up-broken" class="align-middle"></iconify-icon></span>
                                                                 6 Floor
                                                            </span>
                                                       </div>
                                                  </div>

                                             </div>
                                             <div class="card-footer bg-light-subtle d-flex justify-content-between align-items-center border-top">
                                                  <p class="fw-medium text-muted text-decoration-line-through fs-16 mb-0">$34,341.00 </p>
                                                  <div>
                                                       <a href="#!" class="link-primary fw-medium">More Inquiry <i class="ri-arrow-right-line align-middle"></i></a>
                                                  </div>
                                             </div>
                                        </div>
                                   </div>
                              </div>

                         </div>
                    </div>
               </div>
               @endsection
