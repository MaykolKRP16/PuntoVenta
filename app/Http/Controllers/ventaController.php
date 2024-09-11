<?php

namespace App\Http\Controllers;

use Barryvdh\DomPDF\Facade\Pdf;
use App\Http\Requests\StoreVentaRequest;
use App\Models\Cliente;
use App\Models\Comprobante;
use App\Models\Producto;
use App\Models\Venta;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class ventaController extends Controller
{
    function __construct()
    {
        $this->middleware('permission:ver-venta|crear-venta|mostrar-venta|eliminar-venta', ['only' => ['index']]);
        $this->middleware('permission:crear-venta', ['only' => ['create', 'store']]);
        $this->middleware('permission:mostrar-venta', ['only' => ['show']]);
        $this->middleware('permission:eliminar-venta', ['only' => ['destroy']]);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $ventas = Venta::with(['comprobante', 'cliente.persona', 'user'])
            ->where('estado', 1)
            ->latest()
            ->get();

        return view('venta.index', compact('ventas'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $subquery = DB::table('compra_producto')
            ->select('producto_id', DB::raw('MAX(created_at) as max_created_at'))
            ->groupBy('producto_id');

        $productos = Producto::join('compra_producto as cpr', function ($join) use ($subquery) {
            $join->on('cpr.producto_id', '=', 'productos.id')
                ->whereIn('cpr.created_at', function ($query) use ($subquery) {
                    $query->select('max_created_at')
                        ->fromSub($subquery, 'subquery')
                        ->whereRaw('subquery.producto_id = cpr.producto_id');
                });
        })
            ->select('productos.nombre', 'productos.id', 'productos.stock', 'cpr.precio_venta')
            ->where('productos.estado', 1)
            ->where('productos.stock', '>', 0)
            ->get();

        $clientes = Cliente::whereHas('persona', function ($query) {
            $query->where('estado', 1);
        })->get();
        $comprobantes = Comprobante::all();

        return view('venta.create', compact('productos', 'clientes', 'comprobantes'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVentaRequest $request)
    {
        try {
            DB::beginTransaction();

            //Llenar mi tabla venta
            $venta = Venta::create($request->validated());

            //Llenar mi tabla venta_producto
            //1. Recuperar los arrays
            $arrayProducto_id = $request->get('arrayidproducto');
            $arrayCantidad = $request->get('arraycantidad');
            $arrayPrecioVenta = $request->get('arrayprecioventa');
            $arrayDescuento = $request->get('arraydescuento');

            //2.Realizar el llenado
            $siseArray = count($arrayProducto_id);
            $cont = 0;

            while ($cont < $siseArray) {
                $venta->productos()->syncWithoutDetaching([
                    $arrayProducto_id[$cont] => [
                        'cantidad' => $arrayCantidad[$cont],
                        'precio_venta' => $arrayPrecioVenta[$cont],
                        'descuento' => $arrayDescuento[$cont]
                    ]
                ]);

                //Actualizar stock
                $producto = Producto::find($arrayProducto_id[$cont]);
                $stockActual = $producto->stock;
                $cantidad = intval($arrayCantidad[$cont]);

                DB::table('productos')
                    ->where('id', $producto->id)
                    ->update([
                        'stock' => $stockActual - $cantidad
                    ]);

                $cont++;
            }

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
        }

        return redirect()->route('ventas.index')->with('success', 'Venta exitosa');
    }

    /**
     * Display the specified resource.
     */
    public function show(Venta $venta)
    {
        return view('venta.show', compact('venta'));
    }

    public function impriVen(Venta $venta)
    {

        // Calcular los valores en el backend
        $subtotal = 0;
        foreach ($venta->productos as $producto) {
            $subtotal += ($producto->pivot->cantidad * $producto->pivot->precio_venta) - $producto->pivot->descuento;
        }

        $impuesto = $subtotal * ($venta->impuesto / 100);
        $total = $subtotal + $impuesto;
        $pdf = Pdf::loadView('venta.impriven', compact('venta', 'subtotal', 'impuesto', 'total'));

        // Descargar el archivo PDF
        return $pdf->download('reporte_Venta.pdf');
    }



    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        Venta::where('id', $id)
            ->update([
                'estado' => 0
            ]);

        return redirect()->route('ventas.index')->with('success', 'Venta eliminada');
    }
    public function reporteVentas()
    {
        // Aquí puedes pasar datos adicionales a la vista si lo necesitas
        return view('venta.reporte');
    }

    public function reporte()
    {
        $productos = Producto::all();
        return view('venta.reporte', compact('productos'));
    }

    public function salesData()
    {
        // Suponiendo que tienes un modelo `Venta` y que almacenas las ventas con una columna `mes` y `total_ventas`.
        $ventas = Venta::selectRaw('MONTH(created_at) as mes, SUM(total) as total_ventas')
            ->groupBy('mes')
            ->pluck('total_ventas', 'mes');

        return response()->json($ventas);
    }

    public function salesDataByProduct(Request $request)
    {
        $producto_id = $request->get('producto_id');

        // Obtener las ventas por mes del producto seleccionado
        $ventas = DB::table('producto_venta') // Tabla pivote entre `ventas` y `productos`
            ->join('ventas', 'producto_venta.venta_id', '=', 'ventas.id')
            ->where('producto_venta.producto_id', $producto_id)
            ->selectRaw('MONTH(ventas.created_at) as mes, SUM(producto_venta.cantidad) as total_ventas')
            ->groupBy('mes')
            ->pluck('total_ventas', 'mes');

        return response()->json($ventas);
    }
}
