import asyncio

async def boil_the_water():
    print("Start Boil water...")
    await asyncio.sleep(5)
    print("Boiled the water")
    return "Boiled Water"

async def cut_veggies():
    print("Start cut the veggies...")
    await asyncio.sleep(3)
    print("Cuted the veggies")
    return "Cuted veggies"

async def cook():
    # First Boil the water
    waterTask = asyncio.create_task(boil_the_water())
    await asyncio.sleep(0)

    # Cutting the veggies meanwhile Boiling the water
    veggies = await cut_veggies()

    water = await waterTask

    print(f"Use {water} And {veggies} Cooking")


asyncio.run(cook())