<template>
  <AppLayout>
    <Head title="Mi Carrito — Nexo Digital Store" />

    <div class="cart-page">
      <!-- Empty state -->
      <div v-if="!cart.length" class="cart-empty-wrap"
        v-motion :initial="{opacity:0,y:30}" :enter="{opacity:1,y:0,transition:{duration:400}}">
        <i class="pi pi-shopping-cart empty-icon" />
        <h2>Tu carrito está vacío</h2>
        <p>Explora el catálogo y añade los productos que desees.</p>
        <Button label="Explorar productos" icon="pi pi-search" size="large" @click="$inertia.visit(route('products.index'))" />
      </div>

      <template v-else>
        <!-- Header -->
        <div class="cart-header">
          <h1 class="cart-title">
            <i class="pi pi-shopping-cart" />
            Mi Carrito
            <Tag :value="`${cart.length} producto${cart.length!==1?'s':''}`" severity="secondary" rounded />
          </h1>
          <Button label="Vaciar carrito" icon="pi pi-trash" severity="danger" text size="small" @click="clearCart" />
        </div>

        <div class="cart-layout">
          <!-- Items -->
          <div class="cart-items">
            <TransitionGroup name="cart-item" tag="div" class="items-list">
              <div v-for="item in cart" :key="item.ulid" class="cart-item">
                <!-- Cover -->
                <div class="item-cover">
                  <img v-if="item.cover_image" :src="item.cover_image" :alt="item.name" />
                  <div v-else class="cover-ph"><i class="pi pi-image" /></div>
                </div>
                <!-- Info -->
                <div class="item-body">
                  <div class="item-chips">
                    <Chip v-if="item.platform" :label="item.platform" class="chip-xs" />
                    <Chip v-if="item.region"   :label="item.region"   class="chip-xs" />
                  </div>
                  <Link :href="route('products.show', item.slug)" class="item-name">{{ item.name }}</Link>
                  <p class="item-delivery"><i class="pi pi-bolt" /> Entrega automática inmediata</p>
                </div>
                <!-- Price -->
                <div class="item-price">
                  <span v-if="item.active_promotion" class="price-original">${{ fmt(item.price_usd) }}</span>
                  <span class="price-final">${{ fmt(item.discounted_price_usd) }}</span>
                  <span v-if="item.cashback_amount_nt" class="price-cashback"><i class="pi pi-star-fill" /> +{{ item.cashback_amount_nt }} NT</span>
                </div>
                <!-- Remove -->
                <Button icon="pi pi-times" text severity="secondary" rounded size="small" v-tooltip.top="'Eliminar'" @click="removeItem(item.ulid)" />
              </div>
            </TransitionGroup>
          </div>

          <!-- Summary -->
          <aside class="cart-summary">
            <h2 class="summary-title">Resumen del pedido</h2>

            <div class="summary-rows">
              <div class="sum-row"><span>Subtotal</span><span>${{ fmt(t.subtotal) }}</span></div>
              <div v-if="t.savings > 0" class="sum-row sum-savings"><span><i class="pi pi-tag" /> Descuentos</span><span>-${{ fmt(t.savings) }}</span></div>
              <div v-if="t.sub_discount > 0" class="sum-row sum-savings"><span><i class="pi pi-id-card" /> {{ t.sub_plan }}</span><span>-${{ fmt(t.sub_discount) }}</span></div>
              <div v-if="t.cashback_nt > 0" class="sum-row sum-nt"><span><i class="pi pi-star-fill" /> Cashback NT</span><span>~{{ fmt(t.cashback_nt, 0) }} NT</span></div>
            </div>

            <Divider />
            <div class="sum-total">
              <span>Total</span>
              <span class="total-amount">${{ fmt(t.subtotal) }}</span>
            </div>

            <Button label="Proceder al pago" icon="pi pi-lock" class="w-full checkout-btn" size="large" @click="$inertia.visit(route('checkout.index'))" />

            <div class="trust-badges">
              <span><i class="pi pi-shield" /> Pago seguro</span>
              <span><i class="pi pi-bolt" /> Entrega inmediata</span>
            </div>

            <div class="nt-callout">
              <i class="pi pi-star-fill" style="color:#fbbf24" />
              <span>Puedes usar NexoTokens para descuento adicional en el checkout.</span>
            </div>

            <Button label="Seguir comprando" icon="pi pi-arrow-left" text class="w-full" @click="$inertia.visit(route('products.index'))" />
          </aside>
        </div>
      </template>
    </div>
  </AppLayout>
</template>

<script setup>
import { computed } from 'vue';
import { Head, Link, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
  cart:   { type: Array,  default: () => [] },
  totals: { type: Object, default: () => ({ count:0, subtotal:0, savings:0 }) },
});

const NT_RATE = 0.10;
function fmt(v, d = 2) { return Number(v ?? 0).toFixed(d); }

// Totals normalizados desde las claves reales del backend
const t = computed(() => ({
  count:       Number(props.totals?.count              ?? 0),
  subtotal:    Number(props.totals?.subtotal_usd        ?? 0),
  savings:     Number(props.totals?.savings_usd         ?? 0),
  sub_discount:Number(props.totals?.subscription_discount_usd ?? 0),
  sub_plan:    props.totals?.subscription_plan ?? null,
  cashback_nt: props.cart.reduce((s, i) => s + Number(i?.cashback_amount_nt ?? 0), 0),
}));

function removeItem(ulid){ router.delete(route('cart.remove', ulid), { preserveScroll:true }); }
function clearCart(){ router.delete(route('cart.clear')); }
</script>

<style scoped>
.cart-page { max-width:1100px; margin:0 auto; padding:1.5rem 1rem 3rem; }

.cart-empty-wrap { display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding:6rem 2rem; gap:1rem; }
.empty-icon { font-size:4rem; color:var(--c-text-subtle); }
.cart-empty-wrap h2 { font-size:1.5rem; font-weight:800; color:var(--c-text); margin:0; }
.cart-empty-wrap p  { color:var(--c-text-muted); margin:0; }

.cart-header { display:flex; align-items:center; justify-content:space-between; margin-bottom:1.5rem; flex-wrap:wrap; gap:0.75rem; }
.cart-title  { display:flex; align-items:center; gap:0.75rem; font-size:1.4rem; font-weight:800; color:var(--c-text); margin:0; }

.cart-layout { display:grid; grid-template-columns:1fr 320px; gap:1.25rem; align-items:start; }
@media(max-width:900px){ .cart-layout{ grid-template-columns:1fr; } }

.items-list { display:flex; flex-direction:column; gap:0.75rem; }
.cart-item  { display:flex; align-items:center; gap:1rem; padding:1rem 1.25rem; background:var(--c-surface); border:1px solid var(--c-border); border-radius:14px; transition:box-shadow 0.2s; }
.cart-item:hover{ box-shadow:0 4px 20px var(--c-shadow); }

.item-cover { width:72px; height:54px; border-radius:8px; overflow:hidden; flex-shrink:0; }
.item-cover img{ width:100%; height:100%; object-fit:cover; }
.cover-ph   { width:100%; height:100%; background:var(--c-card); display:flex; align-items:center; justify-content:center; color:var(--c-text-subtle); }

.item-body  { flex:1; min-width:0; }
.item-chips { display:flex; gap:0.25rem; margin-bottom:0.25rem; }
:deep(.chip-xs .p-chip){ padding:0.1rem 0.4rem !important; font-size:0.65rem !important; }
.item-name  { font-size:0.88rem; font-weight:700; color:var(--c-text); text-decoration:none; display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis; }
.item-name:hover{ color:var(--c-primary); }
.item-delivery{ font-size:0.72rem; color:var(--c-text-subtle); display:flex; align-items:center; gap:0.25rem; margin-top:0.25rem; }
.item-delivery .pi{ color:#fbbf24; }

.item-price { text-align:right; flex-shrink:0; }
.price-original{ font-size:0.75rem; color:var(--c-text-subtle); text-decoration:line-through; display:block; }
.price-final   { font-size:1rem; font-weight:800; color:var(--c-text); display:block; }
.price-cashback{ font-size:0.7rem; color:#fbbf24; display:flex; align-items:center; gap:0.2rem; justify-content:flex-end; margin-top:0.2rem; }

/* Summary */
.cart-summary  { background:var(--c-surface); border:1px solid var(--c-border); border-radius:16px; padding:1.5rem; position:sticky; top:5rem; }
.summary-title { font-size:1rem; font-weight:800; color:var(--c-text); margin:0 0 1rem; }
.summary-rows  { display:flex; flex-direction:column; gap:0.5rem; margin-bottom:0.5rem; }
.sum-row       { display:flex; justify-content:space-between; font-size:0.85rem; color:var(--c-text-muted); }
.sum-savings   { color:#10b981; }
.sum-nt        { color:#fbbf24; }
.sum-total     { display:flex; justify-content:space-between; align-items:center; margin-bottom:1rem; }
.sum-total span:first-child{ font-size:0.9rem; font-weight:700; color:var(--c-text); }
.total-amount  { font-size:1.4rem; font-weight:900; color:var(--c-text); }
.checkout-btn  { margin-bottom:0.75rem; }
.w-full        { width:100%; justify-content:center; }
.trust-badges  { display:flex; justify-content:center; gap:1rem; font-size:0.7rem; color:var(--c-text-subtle); margin:0.75rem 0; }
.trust-badges span{ display:flex; align-items:center; gap:0.3rem; }
.nt-callout    { display:flex; align-items:flex-start; gap:0.5rem; background:rgba(251,191,36,0.06); border:1px solid rgba(251,191,36,0.15); border-radius:8px; padding:0.75rem; margin-bottom:0.75rem; font-size:0.75rem; color:var(--c-text-muted); line-height:1.5; }

/* Transitions */
.cart-item-enter-active{ transition:all 0.3s ease; }
.cart-item-leave-active{ transition:all 0.25s ease; position:absolute; }
.cart-item-enter-from { opacity:0; transform:translateX(-16px); }
.cart-item-leave-to   { opacity:0; transform:translateX(16px); }
</style>
