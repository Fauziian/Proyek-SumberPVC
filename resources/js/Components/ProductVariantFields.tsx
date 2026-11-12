import React from "react";

export interface StockProduct {
  id: number;
  kategori: string;
  jenis: string | null;
  warna: string | null;
  stok_saat_ini?: number;
}

interface Props {
  products: StockProduct[];
  value: number | string;
  onChange: (id: number) => void;
  showStock?: boolean;
}

const unique = (values: string[]) => [...new Set(values)];

export default function ProductVariantFields({ products, value, onChange, showStock = false }: Props) {
  const selected = products.find(product => product.id === Number(value)) ?? products[0];
  const categories = unique(products.map(product => product.kategori));
  const categoryProducts = products.filter(product => product.kategori === selected?.kategori);
  const needsType = selected?.kategori !== "Boloni Gunung";
  const needsColor = selected?.kategori !== "Outsole";
  const types = unique(categoryProducts.map(product => product.jenis).filter((jenis): jenis is string => Boolean(jenis)));
  const colorProducts = needsType ? categoryProducts.filter(product => product.jenis === selected?.jenis) : categoryProducts;
  const colors = unique(colorProducts.map(product => product.warna).filter((warna): warna is string => Boolean(warna)));

  const choose = (kategori: string, jenis?: string | null, warna?: string | null) => {
    const candidates = products.filter(product => product.kategori === kategori);
    const next = candidates.find(product =>
      (jenis === undefined || product.jenis === jenis) && (warna === undefined || product.warna === warna)
    ) ?? candidates[0];
    if (next) onChange(next.id);
  };

  return <>
    <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">
      Kategori Barang
      <select required value={selected?.kategori ?? ""} onChange={event => choose(event.target.value)} className="block w-full rounded-lg border-slate-200 text-xs font-normal normal-case">
        {categories.map(category => <option key={category}>{category}</option>)}
      </select>
    </label>
    {needsType && <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">
      {selected?.kategori === "Tali Jepit" ? "Jenis Jepit" : "Jenis Outsole"}
      <select required value={selected?.jenis ?? ""} onChange={event => choose(selected.kategori, event.target.value)} className="block w-full rounded-lg border-slate-200 text-xs font-normal normal-case">
        {types.map(type => <option key={type}>{type}</option>)}
      </select>
    </label>}
    {needsColor && <label className="space-y-1 text-[10px] font-bold uppercase text-slate-500">
      Warna
      <select required value={selected?.warna ?? ""} onChange={event => choose(selected.kategori, selected.jenis, event.target.value)} className="block w-full rounded-lg border-slate-200 text-xs font-semibold normal-case">
        {colors.map(color => {
          const product = colorProducts.find(item => item.warna === color);
          const stock = product?.stok_saat_ini ?? 0;
          const label = showStock ? color + " — " + stock + " kodi" : color;
          return <option key={color} value={color} style={{ color: stock > 0 ? "#16a34a" : "#dc2626" }}>{label}</option>;
        })}
      </select>
    </label>}
  </>;
}
