# 🌉 Vending Machine IoT Gateway (MQTT Bridge)

Este projeto consiste em um script Python que atua como um **Gateway IoT (Bridge)** para uma Vending Machine inteligente. Ele coleta dados de telemetria e eventos de vendas de um broker MQTT de origem e roteia essas informações de forma estruturada para duas plataformas de IoT em nuvem: **Ubidots** (via MQTT) e **ThingSpeak** (via API REST).

## 📋 Arquitetura do Sistema

1. **Origem (Vending Machine):** Publica dados no broker **HiveMQ Cloud** via MQTT seguro (TLS) nos tópicos `vending/machine/status` e `vending/machine/vendas`.
2. **Bridge (Este Script):** Assina os tópicos usando wildcard (`#`), processa os dados JSON, traduz IDs em informações comerciais e gerencia a cadência de envio.
3. **Destinos:**
   * **Ubidots:** Recebe dados de estoque, status ambiental e eventos comerciais instantaneamente via **MQTT**.
   * **ThingSpeak:** Recebe dados estruturados em *fields* via **API REST (HTTP POST)**, respeitando limites de requisição.

---

## 🚀 Pré-requisitos e Instalação

Certifique-se de ter o Python 3 instalado (testado em ambiente Linux/Ubuntu). Instale as dependências necessárias utilizando o `pip`:

```bash
pip install paho-mqtt requests
```

### Nota: O código utiliza a versão mais recente da API de callbacks do Paho MQTT (CallbackAPIVersion.VERSION1).

## ⚙️ Configurações Principais (Como adaptar)
Antes de rodar o código em produção, as credenciais e parâmetros na Seção 1 do script devem ser validadas.

### 1. Conexão HiveMQ (Origem)
`HIVEMQ_BROKER`: URL do seu cluster HiveMQ.

`HIVEMQ_PORT`: Padrão 8883 (exige TLS/SSL).

`TOPIC_SUB`: Utiliza o wildcard vending/machine/# para escutar múltiplos sub-tópicos simultaneamente.

### 2. Conexão Ubidots (Destino 1)
`UBIDOTS_TOKEN`: Seu Token de acesso do Ubidots.

`DEVICE_LABEL`: Nome do dispositivo onde as variáveis serão criadas (ex: vending-machine).

### 3. Conexão ThingSpeak (Destino 2)
`TS_WRITE_API_KEY`: A chave Write API Key do seu canal (não confunda com a senha MQTT do ThingSpeak).


## 🧠 Lógica e Funcionalidades de Destaque
Gestão Assíncrona de Rate Limit (ThingSpeak): A API gratuita do ThingSpeak exige um intervalo de 15 segundos entre os envios. O script utiliza a biblioteca threading para colocar as requisições HTTP em uma fila com atraso, impedindo que o loop principal do MQTT trave enquanto aguarda esse tempo.

Formatação Automática de Variáveis (Snake Case): A função `to_snake_case()` converte nomes de produtos (ex: "Batata Chips Clássica") para labels amigáveis de banco de dados (ex: `batata_chips_classica`) para o Ubidots em tempo real.

Geração Dinâmica de Client ID: O script não força um `client_id`fixo no HiveMQ, evitando conflitos de desconexão caso o hardware físico/simulador utilize o mesmo ID.

## 🛠️ Como Modificar e Expandir
Adicionar novos produtos
Para adicionar um novo produto à Vending Machine, basta atualizar o dicionário PRODUTOS na Seção 2 do código. O script fará o resto automaticamente:

```
PRODUTOS = {
    # ... produtos existentes ...
    17: {"nome": "Novo Energético", "preco": 8.00}
}
```

## Alterar o mapeamento do ThingSpeak

Se você adicionar novos sensores e precisar enviá-los para novos Fields no ThingSpeak, vá até a função process_vending_data e adicione o campo no dicionário `ts_payload`:

```
ts_payload = {
    "api_key": TS_WRITE_API_KEY,
    "field3": temp,
    # Adicione novos fields aqui...
}
```
## ▶️ Como Executar
Para iniciar a ponte, basta rodar o script no terminal:

```
python3 brigde_rest_mqtt.py
```

