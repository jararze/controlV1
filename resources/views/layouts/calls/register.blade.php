<div class="modal" data-modal="false" data-modal-backdrop-static="true" id="make_call">
    <div class="modal-content max-w-[600px] top-[15%]">
        <div class="modal-header py-4 px-5">
            <h3 class="modal-title">
                Registrar Llamada
            </h3>
            <button class="btn btn-sm btn-icon btn-light btn-clear shrink-0" data-modal-dismiss="true">
                <i class="ki-filled ki-cross">
                </i>
            </button>
        </div>
        <div class="modal-body pb-5">
            <form id="call-form" action="{{ route('work.matrix.call.save') }}" method="POST">
                @csrf
                <input type="hidden" name="planilla" id="modal-planilla" value="">
                <input type="hidden" name="batchid" id="modal-batchid" value="">

                <div class="mb-4">
                    <label for="note" class="block text-sm font-medium text-gray-700">Observación:</label>
                    <textarea id="note" name="note" rows="4"
                              class="textarea textarea-sm w-full border-gray-300 rounded-md"
                              placeholder="Escribe tu observación aquí"></textarea>
                    <span class="text-xs text-gray-500">Puedes escribir cualquier detalle relevante aquí.</span>
                </div>

                <div class="grid grid-cols-3 gap-4">

                    <div class="mb-4">
                        <label for="destino" class="block text-sm font-medium text-gray-700">En destino?:</label>
                        <select name="destino" id="destino" class="select w-full">
                            <option value="si">Si</option>
                            <option value="no">No</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="descargo" class="block text-sm font-medium text-gray-700">Descargo?:</label>
                        <select name="descargo" id="descargo" class="select w-full">
                            <option value="--">Seleccione en destino?</option>
                            <option value="si">Si</option>
                            <option value="no">No</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="espera" class="block text-sm font-medium text-gray-700">Tiempo Espera?:</label>
                        <div class="flex items-center gap-2">
                            <button type="button" id="decrementDescarga" class="btn btn-sm btn-light border-gray-300">-</button>
                            <input type="text" id="dialerDescarga" name="espera" class="input w-24 text-center border-gray-300 rounded-md" value="0" readonly />
                            <button type="button" id="incrementDescarga" class="btn btn-sm btn-light border-gray-300">+</button>
                        </div>
                    </div>


                    <div class="mb-4">
                        <label for="llegara_en_horario" class="block text-sm font-medium text-gray-700">Llego/ara en horario?:</label>
                        <select name="llegara_en_horario" id="llegara_en_horario" class="select w-full">
                            <option value="si">Si</option>
                            <option value="no">No</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="fuera_hora" class="block text-sm font-medium text-gray-700">Cuán fuera de horario?:</label>
                        <div class="flex items-center gap-2">
                            <button type="button" id="decrement" class="btn btn-sm btn-light border-gray-300">-</button>
                            <input type="text" id="fuera_hora" name="fuera_hora" class="input w-24 text-center border-gray-300 rounded-md" value="0" readonly />
                            <button type="button" id="increment" class="btn btn-sm btn-light border-gray-300">+</button>
                        </div>
                    </div>

                    <div class="mb-4">
                        <label for="diesel" class="block text-sm font-medium text-gray-700">Diesel:</label>
                        <select name="diesel" id="diesel" class="select w-full">
                            <option value="si">Si</option>
                            <option value="no">No</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="fila" class="block text-sm font-medium text-gray-700">En fila diesel?:</label>
                        <select name="fila" id="fila" class="select w-full">
                            <option value="si">Si</option>
                            <option value="no">No</option>
                            <option value="ira">Ira</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="falla_mecanica" class="block text-sm font-medium text-gray-700">Falla mecanica?:</label>
                        <select name="falla_mecanica" id="falla_mecanica" class="select w-full">
                            <option value="si">Si</option>
                            <option value="no">No</option>
                        </select>
                    </div>

                    <div class="mb-4">
                        <label for="bloqueo" class="block text-sm font-medium text-gray-700">En bloqueo?:</label>
                        <select name="bloqueo" id="bloqueo" class="select w-full">
                            <option value="si">Si</option>
                            <option value="no">No</option>
                        </select>
                    </div>
                </div>


                <button type="submit" class="btn btn-primary w-full">Guardar</button>
            </form>
        </div>
    </div>
</div>
